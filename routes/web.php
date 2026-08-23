<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\CaseCoordinationController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyPreviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');

Route::middleware(['auth', 'account.active'])->scopeBindings()->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.preview');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.preview');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
    Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');

    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}/follow-ups/create', [FollowUpController::class, 'create'])->name('cases.follow-ups.create');
    Route::post('/cases/{case}/follow-ups', [FollowUpController::class, 'store'])->name('cases.follow-ups.store');
    Route::get('/cases/{case}/follow-ups/{followUp}/edit', [FollowUpController::class, 'edit'])->name('cases.follow-ups.edit');
    Route::patch('/cases/{case}/follow-ups/{followUp}', [FollowUpController::class, 'update'])->name('cases.follow-ups.update');
    Route::get('/cases/{case}/resolve', [CaseController::class, 'resolveForm'])->name('cases.resolve.form');
    Route::post('/cases/{case}/resolve', [CaseController::class, 'resolve'])->name('cases.resolve');
    Route::post('/cases/{case}/assign', [AssignmentController::class, 'assignCase'])->name('cases.assign');
    Route::post('/cases/{case}/coordinations', [CaseCoordinationController::class, 'store'])->name('cases.coordinations.store');
    Route::patch('/cases/{case}/coordinations/{coordination}', [CaseCoordinationController::class, 'update'])->name('cases.coordinations.update');
    Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/show', [StudentController::class, 'legacy'])->name('students.legacy');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

    Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
    Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
    Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('/assignments/classes', [AssignmentController::class, 'index'])->name('assignments.classes.index');
    Route::get('/assignments/classes/manage', [AssignmentController::class, 'manage'])->name('assignments.classes.manage');
    Route::post('/assignments/classes', [AssignmentController::class, 'storeClassAssignment'])->name('assignments.classes.store');
    Route::get('/assignments/cases', [AssignmentController::class, 'caseIndex'])->name('assignments.cases.index');

    Route::get('/corrections', [CorrectionController::class, 'index'])->name('corrections.index');
    Route::get('/corrections/create', [CorrectionController::class, 'create'])->name('corrections.create');
    Route::post('/corrections', [CorrectionController::class, 'store'])->name('corrections.store');
    Route::get('/corrections/{correction}', [CorrectionController::class, 'show'])->name('corrections.show');
    Route::post('/corrections/{correction}/verify', [CorrectionController::class, 'verify'])->name('corrections.verify');
    Route::post('/corrections/{correction}/process-master', [CorrectionController::class, 'processMaster'])->name('corrections.process-master');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('/achievements/create', [AchievementController::class, 'create'])->name('achievements.create');
    Route::post('/achievements', [AchievementController::class, 'store'])->name('achievements.store');
    Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])->name('achievements.show');
    Route::get('/achievements/{achievement}/edit', [AchievementController::class, 'edit'])->name('achievements.edit');
    Route::patch('/achievements/{achievement}', [AchievementController::class, 'update'])->name('achievements.update');
    Route::post('/achievements/{achievement}/verify', [AchievementController::class, 'verify'])->name('achievements.verify');

    Route::get('/data-master', [DataMasterController::class, 'index'])->name('data-master.index');
    Route::post('/data-master/dapodik/sync', [DataMasterController::class, 'synchronize'])->name('data-master.dapodik.sync');
    Route::post('/data-master/etatib/sync', [DataMasterController::class, 'synchronizeEtatib'])->name('data-master.etatib.sync');

    Route::view('/access-denied', 'pages.system.access-denied')->name('access.denied');

    // Bookmark pratinjau lama hanya menerima GET/HEAD dan tidak lagi memuat fixture.
    Route::get('/_preview/dashboard', LegacyPreviewController::class)->defaults('destination', 'dashboard.preview')->name('fixtures.dashboard');
    Route::get('/_preview/notifications', LegacyPreviewController::class)->defaults('destination', 'notifications.preview')->name('fixtures.notifications');
    Route::get('/_preview/cases', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.index');
    Route::get('/_preview/cases/create', LegacyPreviewController::class)->defaults('destination', 'cases.create')->name('fixtures.cases.create');
    Route::get('/_preview/cases/show', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.show');
    Route::get('/_preview/cases/follow-up', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.follow-up');
    Route::get('/_preview/cases/resolve', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.resolve');
    Route::get('/_preview/students', LegacyPreviewController::class)->defaults('destination', 'students.index')->name('fixtures.students.index');
    Route::get('/_preview/students/show', LegacyPreviewController::class)->defaults('destination', 'students.legacy')->name('fixtures.students.show');
    Route::get('/_preview/consultations/show', LegacyPreviewController::class)->defaults('destination', 'consultations.index')->name('fixtures.consultations.show');
    Route::get('/_preview/assignments/cases', LegacyPreviewController::class)->defaults('destination', 'assignments.cases.index')->name('fixtures.assignments.cases.index');
    Route::get('/_preview/corrections', LegacyPreviewController::class)->defaults('destination', 'corrections.index')->name('fixtures.corrections.index');
    Route::get('/_preview/corrections/create', LegacyPreviewController::class)->defaults('destination', 'corrections.create')->name('fixtures.corrections.create');
    Route::get('/_preview/corrections/show', LegacyPreviewController::class)->defaults('destination', 'corrections.index')->name('fixtures.corrections.show');
    Route::get('/_preview/history', LegacyPreviewController::class)->defaults('destination', 'history.index')->name('fixtures.history.index');
});
