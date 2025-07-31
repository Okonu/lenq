<?php

namespace App\Http\Controllers;

use App\Models\FirmMember;
use App\Models\LawFirm;
use App\Models\LegalCase;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks.
     */
    public function index()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('viewTasks', $lawFirm);

        $request = request();
        $status = $request->status ?? 'all';
        $priority = $request->priority ?? 'all';
        $assignedTo = $request->assigned_to ?? 'all';
        $dueDate = $request->due_date ?? 'all';
        $caseId = $request->case_id ?? null;

        $query = Task::where('law_firm_id', $lawFirm->id);

        if ($status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', Task::STATUS_COMPLETED);
        }

        if ($priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($assignedTo !== 'all') {
            if ($assignedTo === 'me') {
                $query->where('assigned_to', $firmMember->id);
            } elseif ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $assignedTo);
            }
        }

        if ($dueDate !== 'all') {
            if ($dueDate === 'today') {
                $query->whereDate('due_date', now()->toDateString());
            } elseif ($dueDate === 'tomorrow') {
                $query->whereDate('due_date', now()->addDay()->toDateString());
            } elseif ($dueDate === 'this_week') {
                $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dueDate === 'next_week') {
                $query->whereBetween('due_date', [now()->addWeek()->startOfWeek(), now()->addWeek()->endOfWeek()]);
            } elseif ($dueDate === 'overdue') {
                $query->whereDate('due_date', '<', now()->toDateString())
                    ->where('status', '!=', Task::STATUS_COMPLETED);
            } elseif ($dueDate === 'no_due_date') {
                $query->whereNull('due_date');
            }
        }

        if ($caseId) {
            $query->where('legal_case_id', $caseId);
        }

        $tasks = $query->with(['legalCase:id,title', 'assignedTo.user:id,name'])
            ->orderBy('due_date')
            ->get();

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                ];
            });

        $cases = LegalCase::where('law_firm_id', $lawFirm->id)
            ->where('status', '!=', LegalCase::STATUS_CLOSED)
            ->select('id', 'title')
            ->get();

        return Inertia::render('Tasks/Index', [
            'lawFirm' => $lawFirm,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
            'cases' => $cases,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assignedTo,
                'due_date' => $dueDate,
                'case_id' => $caseId,
            ],
            'userRole' => $firmMember->role,
        ]);
    }

    /**
     * Show the form for creating a new task.
     */
    public function create()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createTask', $lawFirm);

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                ];
            });

        $cases = LegalCase::where('law_firm_id', $lawFirm->id)
            ->where('status', '!=', LegalCase::STATUS_CLOSED)
            ->select('id', 'title')
            ->get();

        $caseId = request()->case_id;
        $selectedCase = null;

        if ($caseId) {
            $selectedCase = LegalCase::where('id', $caseId)
                ->where('law_firm_id', $lawFirm->id)
                ->first();
        }

        return Inertia::render('Tasks/Create', [
            'lawFirm' => $lawFirm,
            'teamMembers' => $teamMembers,
            'cases' => $cases,
            'selectedCase' => $selectedCase,
        ]);
    }

    /**
     * Store a newly created task in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
            'assigned_to' => 'nullable|exists:firm_members,id',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('createTask', $lawFirm);

        if ($request->legal_case_id) {
            $case = LegalCase::findOrFail($request->legal_case_id);
            if ($case->law_firm_id !== $lawFirm->id) {
                return back()->with('error', 'Selected case does not belong to your firm.');
            }
        }

        if ($request->assigned_to) {
            $member = FirmMember::findOrFail($request->assigned_to);
            if ($member->law_firm_id !== $lawFirm->id) {
                return back()->with('error', 'Selected team member does not belong to your firm.');
            }
        }

        $task = Task::create([
            'law_firm_id' => $lawFirm->id,
            'legal_case_id' => $request->legal_case_id,
            'assigned_to' => $request->assigned_to,
            'created_by' => $firmMember->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => Task::STATUS_PENDING,
        ]);

        if ($request->legal_case_id) {
            return redirect()->route('cases.show', $request->legal_case_id)
                ->with('success', 'Task created successfully!');
        } else {
            return redirect()->route('tasks.index')
                ->with('success', 'Task created successfully!');
        }
    }

    /**
     * Display the specified task.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load(['legalCase', 'assignedTo.user', 'createdBy.user']);

        return Inertia::render('Tasks/Show', [
            'task' => $task,
        ]);
    }

    /**
     * Show the form for editing the specified task.
     */
    public function edit(Task $task)
    {
        $this->authorize('update', $task);

        $lawFirm = LawFirm::findOrFail($task->law_firm_id);

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                ];
            });

        $cases = LegalCase::where('law_firm_id', $lawFirm->id)
            ->where('status', '!=', LegalCase::STATUS_CLOSED)
            ->select('id', 'title')
            ->get();

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
            'teamMembers' => $teamMembers,
            'cases' => $cases,
        ]);
    }

    /**
     * Update the specified task in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'legal_case_id' => 'nullable|exists:legal_cases,id',
            'assigned_to' => 'nullable|exists:firm_members,id',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,deferred',
        ]);

        if ($request->legal_case_id) {
            $case = LegalCase::findOrFail($request->legal_case_id);
            if ($case->law_firm_id !== $task->law_firm_id) {
                return back()->with('error', 'Selected case does not belong to your firm.');
            }
        }

        if ($request->assigned_to) {
            $member = FirmMember::findOrFail($request->assigned_to);
            if ($member->law_firm_id !== $task->law_firm_id) {
                return back()->with('error', 'Selected team member does not belong to your firm.');
            }
        }

        $markingAsCompleted = $request->status === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED;

        $task->update([
            'legal_case_id' => $request->legal_case_id,
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => $request->status,
            'completed_at' => $markingAsCompleted ? now() : $task->completed_at,
        ]);

        if ($request->redirect_to_case && $task->legal_case_id) {
            return redirect()->route('cases.show', $task->legal_case_id)
                ->with('success', 'Task updated successfully!');
        } else {
            return redirect()->route('tasks.show', $task->id)
                ->with('success', 'Task updated successfully!');
        }
    }

    /**
     * Update the task status.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,deferred',
        ]);

        $markingAsCompleted = $request->status === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED;

        $task->update([
            'status' => $request->status,
            'completed_at' => $markingAsCompleted ? now() : $task->completed_at,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        if (request()->redirect_to_case && $task->legal_case_id) {
            return redirect()->route('cases.show', $task->legal_case_id)
                ->with('success', 'Task deleted successfully!');
        } else {
            return redirect()->route('tasks.index')
                ->with('success', 'Task deleted successfully!');
        }
    }

    public function calendar()
    {
        $user = Auth::user();
        $firmMember = FirmMember::where('user_id', $user->id)->first();

        if (!$firmMember) {
            return redirect()->route('lawfirm.create');
        }

        $lawFirm = LawFirm::findOrFail($firmMember->law_firm_id);

        $this->authorize('viewTasks', $lawFirm);

        $month = request()->has('month') ? (int)request()->month : now()->month;
        $year = request()->has('year') ? (int)request()->year : now()->year;
        $day = request()->has('day') ? (int)request()->day : 1;
        $view = request()->view ?? 'month';
        $assignedTo = request()->assigned_to ?? 'all';

        $query = Task::where('law_firm_id', $lawFirm->id)
            ->whereNotNull('due_date');

        if ($assignedTo !== 'all') {
            if ($assignedTo === 'me') {
                $query->where('assigned_to', $firmMember->id);
            } elseif ($assignedTo === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $assignedTo);
            }
        }

        if ($view === 'month') {
            $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
            $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();
        } elseif ($view === 'week') {
            $date = now()->setYear($year)->setMonth($month)->setDay($day);
            $startDate = $date->copy()->startOfWeek();
            $endDate = $date->copy()->endOfWeek();
        } elseif ($view === 'day') {
            $date = now()->setYear($year)->setMonth($month)->setDay($day);
            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();
        } else {
            $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
            $endDate = now()->setYear($year)->setMonth($month)->endOfMonth();
        }

        $query->whereBetween('due_date', [$startDate, $endDate]);

        $tasks = $query->with(['legalCase:id,title', 'assignedTo.user:id,name'])
            ->get();

        $teamMembers = FirmMember::where('law_firm_id', $lawFirm->id)
            ->where('status', FirmMember::STATUS_ACTIVE)
            ->with('user:id,name')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->user->name,
                ];
            });

        return Inertia::render('Tasks/Calendar', [
            'lawFirm' => $lawFirm,
            'tasks' => $tasks,
            'teamMembers' => $teamMembers,
            'filters' => [
                'month' => $month,
                'year' => $year,
                'view' => $view,
                'day' => $day,
                'assigned_to' => $assignedTo,
            ],
            'userRole' => $firmMember->role,
        ]);
    }
}
