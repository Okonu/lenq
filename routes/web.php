<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\LegalChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LawFirmController;
use App\Http\Controllers\FirmMemberController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/invitations/{token}', [FirmMemberController::class, 'acceptInvitation'])
    ->name('invitations.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Law Firm
    Route::get('/lawfirm/dashboard', [LawFirmController::class, 'dashboard'])->name('lawfirm.dashboard');
    Route::get('/lawfirm/create', [LawFirmController::class, 'create'])->name('lawfirm.create');
    Route::post('/lawfirm', [LawFirmController::class, 'store'])->name('lawfirm.store');
    Route::get('/lawfirm/{lawFirm}', [LawFirmController::class, 'show'])->name('lawfirm.show');
    Route::get('/lawfirm/{lawFirm}/edit', [LawFirmController::class, 'edit'])->name('lawfirm.edit');
    Route::put('/lawfirm/{lawFirm}', [LawFirmController::class, 'update'])->name('lawfirm.update');
    Route::delete('/lawfirm/{lawFirm}', [LawFirmController::class, 'destroy'])->name('lawfirm.destroy');

    // Firm Members
    Route::get('/firm/members', [FirmMemberController::class, 'index'])->name('firm.members.index');
    Route::get('/firm/members/create', [FirmMemberController::class, 'create'])->name('firm.members.create');
    Route::post('/firm/members', [FirmMemberController::class, 'store'])->name('firm.members.store');
    Route::put('/firm/members/{member}', [FirmMemberController::class, 'update'])->name('firm.members.update');
    Route::delete('/firm/members/{member}', [FirmMemberController::class, 'destroy'])->name('firm.members.destroy');

    // Clients
    Route::resource('clients', ClientController::class);

    // Legal Cases
    Route::resource('cases', LegalCaseController::class);

    // Documents
    Route::resource('documents', LegalDocumentController::class);

    // Tasks
    Route::get('/tasks/calendar', [TaskController::class, 'calendar'])->name('tasks.calendar');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::resource('tasks', TaskController::class);

    // Chat
    Route::get('/chat', [LegalChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/create', [LegalChatController::class, 'create'])->name('chat.create');
    Route::post('/chat', [LegalChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{conversation}', [LegalChatController::class, 'show'])->name('chat.show');
    Route::put('/chat/{conversation}', [LegalChatController::class, 'update'])->name('chat.update');
    Route::post('/chat/{conversation}/message', [LegalChatController::class, 'sendMessage'])->name('chat.message.store');
    Route::delete('/chat/{conversation}', [LegalChatController::class, 'destroy'])->name('chat.destroy');
    Route::post('/chat/document', [LegalChatController::class, 'uploadDocument'])->name('chat.document.upload');
    Route::get('/chat/document/{document}', [LegalChatController::class, 'getDocumentStatus'])->name('chat.document.status');
    Route::delete('/chat/document/{document}', [LegalChatController::class, 'deleteDocument'])->name('chat.document.delete');

    Route::post('/api/chat/create', [LegalChatController::class, 'apiCreateConversation'])->name('api.chat.create');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
