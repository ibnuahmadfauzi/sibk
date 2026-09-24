<?php

declare(strict_types=1);

use App\Http\Controllers\AcademicYearActivationController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\AcademicYearPreparationController;
use App\Http\Controllers\Admin\DapodikReconciliationController;
use App\Http\Controllers\Admin\DataMasterController;
use App\Http\Controllers\Admin\IntegrationSettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\UserPasswordResetController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyPreviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDepartureController;
use App\Models\IntegrationSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:5,1'])->name('login.store');

Route::middleware(['auth', 'account.active'])->scopeBindings()->group(function (): void {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/account/change-password', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::patch('/account/change-password', [AccountPasswordController::class, 'update'])->name('account.password.update');

    Route::middleware('password.changed')->group(function (): void {
        require __DIR__.'/bk-services.php';
        require __DIR__.'/waka.php';

        Route::get('/account', [AccountController::class, 'index'])->name('account.index');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.preview');

        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::patch('/admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::post('/admin/users/{user}/reset-password', UserPasswordResetController::class)->name('admin.users.reset-password');

        Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
        Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
        Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
        Route::delete('/cases/{case}', [CaseController::class, 'destroy'])->name('cases.destroy');
        Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/show', [StudentController::class, 'legacy'])->name('students.legacy');
        Route::post('/students/{student}/departure', [StudentDepartureController::class, 'store'])->name('students.departure.store');
        Route::patch('/students/{student}/departure', [StudentDepartureController::class, 'update'])->name('students.departure.update');
        Route::post('/students/{student}/departure/finalize', [StudentDepartureController::class, 'finalize'])->name('students.departure.finalize');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

        Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
        Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
        Route::get('/consultations/{consultation}/edit', [ConsultationController::class, 'edit'])->name('consultations.edit');
        Route::patch('/consultations/{consultation}', [ConsultationController::class, 'update'])->name('consultations.update');
        Route::delete('/consultations/{consultation}', [ConsultationController::class, 'destroy'])->name('consultations.destroy');
        Route::get('/consultations/{consultation}', [ConsultationController::class, 'show'])->name('consultations.show');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
        Route::get('/reports/records/{type}/{id}/preview', [ReportController::class, 'recordPreview'])
            ->whereIn('type', ['case', 'consultation'])
            ->whereNumber('id')
            ->name('reports.records.preview');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/assignments/classes', [AssignmentController::class, 'index'])->name('assignments.classes.index');
        Route::get('/assignments/classes/manage', [AssignmentController::class, 'manage'])->name('assignments.classes.manage');
        Route::post('/assignments/classes', [AssignmentController::class, 'storeClassAssignment'])->name('assignments.classes.store');
        Route::post('/assignments/classes/batch', [AssignmentController::class, 'storeClassAssignments'])
            ->name('assignments.classes.batch');
        Route::delete('/assignments/classes/{classroom}', [AssignmentController::class, 'destroyClassAssignment'])
            ->name('assignments.classes.destroy');
        Route::post('/assignments/academic-years/{academicYear}/activate', [AcademicYearActivationController::class, 'store'])
            ->name('assignments.academic-years.activate');
        Route::post('/assignments/academic-years/{academicYear}/restore-previous', [AcademicYearActivationController::class, 'restorePrevious'])
            ->name('assignments.academic-years.restore-previous');
        Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
        Route::get('/achievements/create', [AchievementController::class, 'create'])->name('achievements.create');
        Route::post('/achievements', [AchievementController::class, 'store'])->name('achievements.store');
        Route::get('/achievements/{achievement}', [AchievementController::class, 'show'])->name('achievements.show');
        Route::get('/achievements/{achievement}/edit', [AchievementController::class, 'edit'])->name('achievements.edit');
        Route::patch('/achievements/{achievement}', [AchievementController::class, 'update'])->name('achievements.update');
        Route::post('/achievements/{achievement}/verify', [AchievementController::class, 'verify'])->name('achievements.verify');

        Route::get('/data-master', [DataMasterController::class, 'index'])
            ->middleware('cache.headers:no_store')
            ->name('data-master.index');
        Route::post('/data-master/academic-years', [AcademicYearPreparationController::class, 'store'])
            ->name('data-master.academic-years.store');
        Route::delete('/data-master/academic-years/{academicYear}', [AcademicYearPreparationController::class, 'destroy'])
            ->name('data-master.academic-years.destroy');
        Route::post('/data-master/academic-years/{academicYear}/roster-imports', [AcademicYearPreparationController::class, 'storeRoster'])
            ->name('data-master.academic-years.roster-imports.store');
        Route::post('/data-master/dapodik/sync', [DataMasterController::class, 'synchronize'])->name('data-master.dapodik.sync');
        Route::get('/data-master/dapodik/previews/{syncRun}', [DapodikReconciliationController::class, 'show'])
            ->middleware('cache.headers:no_store')
            ->name('data-master.dapodik.previews.show');
        Route::patch('/data-master/dapodik/previews/{syncRun}/items/{item}', [DapodikReconciliationController::class, 'update'])
            ->middleware('cache.headers:no_store')
            ->name('data-master.dapodik.previews.items.update');
        Route::post('/data-master/dapodik/previews/{syncRun}/apply', [DapodikReconciliationController::class, 'apply'])
            ->middleware('cache.headers:no_store')
            ->name('data-master.dapodik.previews.apply');
        Route::post('/data-master/etatib/sync', [DataMasterController::class, 'synchronizeEtatib'])->name('data-master.etatib.sync');
        Route::patch('/data-master/integrations/{provider}', [IntegrationSettingController::class, 'update'])
            ->whereIn('provider', IntegrationSetting::PROVIDERS)
            ->middleware('cache.headers:no_store')
            ->name('data-master.integrations.update');
        Route::post('/data-master/integrations/{provider}/test', [IntegrationSettingController::class, 'test'])
            ->whereIn('provider', IntegrationSetting::PROVIDERS)
            ->middleware(['cache.headers:no_store', 'throttle:integration-test'])
            ->name('data-master.integrations.test');
        Route::post('/data-master/integrations/{provider}/activate', [IntegrationSettingController::class, 'activate'])
            ->whereIn('provider', IntegrationSetting::PROVIDERS)
            ->middleware('cache.headers:no_store')
            ->name('data-master.integrations.activate');
        Route::post('/data-master/integrations/{provider}/deactivate', [IntegrationSettingController::class, 'deactivate'])
            ->whereIn('provider', IntegrationSetting::PROVIDERS)
            ->middleware('cache.headers:no_store')
            ->name('data-master.integrations.deactivate');

        Route::view('/access-denied', 'pages.system.access-denied')->name('access.denied');

        // Bookmark pratinjau lama hanya menerima GET/HEAD dan tidak lagi memuat fixture.
        Route::get('/_preview/dashboard', LegacyPreviewController::class)->defaults('destination', 'dashboard.preview')->name('fixtures.dashboard');
        Route::get('/_preview/cases', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.index');
        Route::get('/_preview/cases/create', LegacyPreviewController::class)->defaults('destination', 'cases.create')->name('fixtures.cases.create');
        Route::get('/_preview/cases/show', LegacyPreviewController::class)->defaults('destination', 'cases.index')->name('fixtures.cases.show');
        Route::get('/_preview/students', LegacyPreviewController::class)->defaults('destination', 'students.index')->name('fixtures.students.index');
        Route::get('/_preview/students/show', LegacyPreviewController::class)->defaults('destination', 'students.legacy')->name('fixtures.students.show');
        Route::get('/_preview/consultations/show', LegacyPreviewController::class)->defaults('destination', 'consultations.index')->name('fixtures.consultations.show');
    });
});
