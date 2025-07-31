<?php

namespace App\Services;

use App\Models\User;
use App\Models\FirmMember;
use App\Models\LegalCase;
use App\Models\Task;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Notifications\Notification as LaravelNotification;

class NotificationService
{
    /**
     * Send case assignment notification
     */
    public function notifyNewCaseAssignment(LegalCase $case, User $assignedUser, string $role): void
    {
        $notification = new \App\Notifications\CaseAssignmentNotification($case, $role);

        $assignedUser->notify($notification);

        $this->createDatabaseNotification($assignedUser, [
            'type' => 'case_assignment',
            'title' => 'New Case Assignment',
            'message' => "You've been assigned as {$role} to case: {$case->title}",
            'action_url' => route('cases.show', $case->id),
            'metadata' => [
                'case_id' => $case->id,
                'role' => $role,
                'assigned_by' => auth()->user()->name,
            ],
        ]);

        Log::info('Case assignment notification sent', [
            'case_id' => $case->id,
            'user_id' => $assignedUser->id,
            'role' => $role,
        ]);
    }

    /**
     * Send task assignment/update notifications
     */
    public function notifyTaskAssignment(Task $task, ?User $previousAssignee = null): void
    {
        $assignedUser = $task->assignedUser;

        if (!$assignedUser) return;

        // Notify new assignee
        $notification = new \App\Notifications\TaskAssignmentNotification($task);
        $assignedUser->notify($notification);

        $this->createDatabaseNotification($assignedUser, [
            'type' => 'task_assignment',
            'title' => 'New Task Assignment',
            'message' => "You've been assigned a new task: {$task->title}",
            'action_url' => route('tasks.show', $task->id),
            'priority' => $this->getNotificationPriority($task->priority),
            'metadata' => [
                'task_id' => $task->id,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
                'case_id' => $task->legal_case_id,
            ],
        ]);

        // Notify previous assignee if task was reassigned
        if ($previousAssignee && $previousAssignee->id !== $assignedUser->id) {
            $this->createDatabaseNotification($previousAssignee, [
                'type' => 'task_reassignment',
                'title' => 'Task Reassigned',
                'message' => "Task '{$task->title}' has been reassigned to {$assignedUser->name}",
                'action_url' => route('tasks.show', $task->id),
                'metadata' => [
                    'task_id' => $task->id,
                    'new_assignee' => $assignedUser->name,
                ],
            ]);
        }
    }

    /**
     * Send task deadline reminders
     */
    public function sendTaskDeadlineReminders(): void
    {
        // Tasks due in 24 hours
        $tasksDueSoon = Task::with(['assignedUser', 'legalCase'])
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDay())
            ->get();

        foreach ($tasksDueSoon as $task) {
            if ($task->assignedUser) {
                $this->notifyTaskDeadlineApproaching($task);
            }
        }

        // Overdue tasks
        $overdueTasks = Task::with(['assignedUser', 'legalCase'])
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueTasks as $task) {
            if ($task->assignedUser) {
                $this->notifyTaskOverdue($task);
            }
        }
    }

    /**
     * Notify document analysis completion
     */
    public function notifyDocumentAnalysisComplete($document): void
    {
        $user = $document->user;

        $notification = new \App\Notifications\DocumentAnalysisCompleteNotification($document);
        $user->notify($notification);

        $this->createDatabaseNotification($user, [
            'type' => 'document_analysis',
            'title' => 'Document Analysis Complete',
            'message' => "Analysis completed for: {$document->title}",
            'action_url' => route('documents.show', $document->id),
            'metadata' => [
                'document_id' => $document->id,
                'analysis_score' => $document->analysis['score'] ?? null,
                'case_id' => $document->legal_case_id,
            ],
        ]);

        // Notify case team members if document belongs to a case
        if ($document->legal_case_id) {
            $this->notifyCaseTeamMembers($document->legalCase, [
                'type' => 'case_document_analyzed',
                'title' => 'New Document Analysis Available',
                'message' => "Document '{$document->title}' has been analyzed for case: {$document->legalCase->title}",
                'action_url' => route('documents.show', $document->id),
                'exclude_user_id' => $user->id,
            ]);
        }
    }

    /**
     * Notify about new conversation in case
     */
    public function notifyNewCaseConversation($conversation): void
    {
        if (!$conversation->legal_case_id) return;

        $case = $conversation->legalCase;

        $this->notifyCaseTeamMembers($case, [
            'type' => 'case_conversation',
            'title' => 'New Case Conversation',
            'message' => "New AI conversation started for case: {$case->title}",
            'action_url' => route('chat.show', $conversation->id),
            'exclude_user_id' => $conversation->user_id,
        ]);
    }

    /**
     * Send firm-wide announcements
     */
    public function sendFirmAnnouncement(array $data): void
    {
        $firmId = $this->getCurrentFirmId();
        $firmMembers = FirmMember::where('law_firm_id', $firmId)
            ->with('user')
            ->get();

        foreach ($firmMembers as $member) {
            $this->createDatabaseNotification($member->user, [
                'type' => 'firm_announcement',
                'title' => $data['title'],
                'message' => $data['message'],
                'action_url' => $data['action_url'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'metadata' => [
                    'announcement_id' => $data['id'] ?? null,
                    'sent_by' => auth()->user()->name,
                ],
            ]);

            // Send email for high priority announcements
            if (($data['priority'] ?? 'normal') === 'high') {
                $member->user->notify(new \App\Notifications\FirmAnnouncementNotification($data));
            }
        }
    }

    /**
     * Notify case team members
     */
    protected function notifyCaseTeamMembers(LegalCase $case, array $notificationData): void
    {
        $teamMembers = $case->teamMembers()->with('user')->get();

        foreach ($teamMembers as $assignment) {
            if ($assignment->firm_member_id === ($notificationData['exclude_user_id'] ?? null)) {
                continue;
            }

            $this->createDatabaseNotification($assignment->firmMember->user, $notificationData);
        }
    }

    /**
     * Notify task deadline approaching
     */
    protected function notifyTaskDeadlineApproaching(Task $task): void
    {
        $hoursUntilDue = now()->diffInHours($task->due_date);

        $this->createDatabaseNotification($task->assignedUser, [
            'type' => 'task_deadline_approaching',
            'title' => 'Task Due Soon',
            'message' => "Task '{$task->title}' is due in {$hoursUntilDue} hours",
            'action_url' => route('tasks.show', $task->id),
            'priority' => $this->getNotificationPriority($task->priority),
            'metadata' => [
                'task_id' => $task->id,
                'hours_until_due' => $hoursUntilDue,
                'case_id' => $task->legal_case_id,
            ],
        ]);

        // Send email for urgent tasks
        if ($task->priority === 'urgent') {
            $task->assignedUser->notify(new \App\Notifications\TaskDeadlineNotification($task));
        }
    }

    /**
     * Notify task overdue
     */
    protected function notifyTaskOverdue(Task $task): void
    {
        $hoursOverdue = $task->due_date->diffInHours(now());

        $this->createDatabaseNotification($task->assignedUser, [
            'type' => 'task_overdue',
            'title' => 'Overdue Task',
            'message' => "Task '{$task->title}' is {$hoursOverdue} hours overdue",
            'action_url' => route('tasks.show', $task->id),
            'priority' => 'high',
            'metadata' => [
                'task_id' => $task->id,
                'hours_overdue' => $hoursOverdue,
                'case_id' => $task->legal_case_id,
            ],
        ]);

        // Always send email for overdue tasks
        $task->assignedUser->notify(new \App\Notifications\TaskOverdueNotification($task));
    }

    /**
     * Create database notification
     */
    protected function createDatabaseNotification(User $user, array $data): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'metadata' => $data['metadata'] ?? [],
            'read_at' => null,
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(array $notificationIds): int
    {
        return Notification::whereIn('id', $notificationIds)
            ->where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(): int
    {
        return Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        $userId = auth()->id();

        return [
            'unread_count' => Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->count(),
            'high_priority_count' => Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->where('priority', 'high')
                ->count(),
            'today_count' => Notification::where('user_id', $userId)
                ->whereDate('created_at', today())
                ->count(),
            'by_type' => Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysToKeep = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysToKeep))
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * Send digest email with recent notifications
     */
    public function sendDigestEmail(User $user, string $frequency = 'daily'): void
    {
        $since = match($frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            default => now()->subDay(),
        };

        $notifications = Notification::where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        $user->notify(new \App\Notifications\DigestNotification($notifications, $frequency));
    }

    /**
     * Get notification priority based on task priority
     */
    protected function getNotificationPriority(string $taskPriority): string
    {
        return match($taskPriority) {
            'urgent' => 'high',
            'high' => 'high',
            'medium' => 'normal',
            'low' => 'low',
            default => 'normal',
        };
    }

    /**
     * Check if user should receive notification based on preferences
     */
    protected function shouldNotifyUser(User $user, string $notificationType): bool
    {
        $preferences = $user->notification_preferences ?? [];

        // Default to true if no preferences set
        return $preferences[$notificationType] ?? true;
    }

    /**
     * Send real-time notification via WebSocket
     */
    public function sendRealTimeNotification(User $user, array $data): void
    {
        // Integration with Laravel Echo/Pusher for real-time notifications
        broadcast(new \App\Events\NotificationCreated($user, $data));
    }

    /**
     * Send mobile push notification
     */
    public function sendPushNotification(User $user, array $data): void
    {
        // Integration with Firebase Cloud Messaging or similar
        if ($user->fcm_token) {
            // Send push notification
            $this->sendFCMNotification($user->fcm_token, $data);
        }
    }

    /**
     * Send FCM notification (placeholder)
     */
    protected function sendFCMNotification(string $token, array $data): void
    {
        // Implement FCM notification sending
        Log::info('FCM notification sent', [
            'token' => substr($token, 0, 10) . '...',
            'title' => $data['title'],
        ]);
    }

    /**
     * Get current user's firm ID
     */
    protected function getCurrentFirmId(): int
    {
        $firmMember = FirmMember::where('user_id', auth()->id())->first();

        if (!$firmMember) {
            throw new \Exception('User is not associated with any firm');
        }

        return $firmMember->law_firm_id;
    }

    /**
     * Notify about knowledge base updates
     */
    public function notifyKnowledgeBaseUpdate($entry): void
    {
        $firmId = $this->getCurrentFirmId();
        $firmMembers = FirmMember::where('law_firm_id', $firmId)
            ->where('user_id', '!=', $entry->user_id)
            ->with('user')
            ->get();

        foreach ($firmMembers as $member) {
            // Only notify if user has opted in for knowledge base updates
            if ($this->shouldNotifyUser($member->user, 'knowledge_base_updates')) {
                $this->createDatabaseNotification($member->user, [
                    'type' => 'knowledge_base_update',
                    'title' => 'New Knowledge Base Entry',
                    'message' => "New {$entry->type} added: {$entry->title}",
                    'action_url' => route('knowledge-base.show', $entry->id),
                    'metadata' => [
                        'entry_id' => $entry->id,
                        'entry_type' => $entry->type,
                        'category' => $entry->category,
                        'added_by' => $entry->user->name,
                    ],
                ]);
            }
        }
    }

    /**
     * Emergency notification for critical issues
     */
    public function sendEmergencyNotification(array $data): void
    {
        $firmId = $this->getCurrentFirmId();
        $firmMembers = FirmMember::where('law_firm_id', $firmId)
            ->with('user')
            ->get();

        foreach ($firmMembers as $member) {
            // Database notification
            $this->createDatabaseNotification($member->user, [
                'type' => 'emergency',
                'title' => $data['title'],
                'message' => $data['message'],
                'action_url' => $data['action_url'] ?? null,
                'priority' => 'critical',
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Email notification
            $member->user->notify(new \App\Notifications\EmergencyNotification($data));

            // Push notification
            $this->sendPushNotification($member->user, $data);

            // Real-time notification
            $this->sendRealTimeNotification($member->user, $data);
        }
    }
}
