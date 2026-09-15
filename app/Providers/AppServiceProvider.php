<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Dapodik\ConfiguredDapodikConnector;
use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Etatib\ConfiguredEtatibConnector;
use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\IntegrationConfigurationProvider;
use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\AchievementPolicy;
use App\Policies\CasePolicy;
use App\Policies\ConsultationPolicy;
use App\Policies\ReportPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeacherAssignmentPolicy;
use App\Policies\UserPolicy;
use App\Policies\WakaMonitoringPolicy;
use App\Services\IntegrationSettingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IntegrationConfigurationProvider::class, IntegrationSettingService::class);
        $this->app->bind(DapodikConnector::class, ConfiguredDapodikConnector::class);
        $this->app->bind(EtatibConnector::class, ConfiguredEtatibConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        RateLimiter::for('integration-test', static function (Request $request): Limit {
            $userId = $request->user()?->getAuthIdentifier() ?? 'guest';
            $provider = (string) $request->route('provider');

            return Limit::perMinute(5)
                ->by("{$userId}:{$provider}")
                ->response(static fn () => response('Terlalu banyak permintaan.', 429)
                    ->header('Cache-Control', 'no-store'));
        });
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Achievement::class, AchievementPolicy::class);
        Gate::policy(TeacherAssignment::class, TeacherAssignmentPolicy::class);
        Gate::policy(BkCase::class, CasePolicy::class);
        Gate::policy(Consultation::class, ConsultationPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);

        Gate::define('viewReports', fn (User $user): bool => app(ReportPolicy::class)->viewAny($user));
        Gate::define('manageDataMaster', fn (User $user): bool => $user->is_active && $user->hasRole('admin_it'));
        Gate::define('manageCaseAssignments', fn (User $user): bool => $user->is_active && $user->hasRole('koordinator_bk'));
        Gate::define('viewWakaMonitoring', fn (User $user): bool => app(WakaMonitoringPolicy::class)->viewMonitoring($user));
        Gate::define('exportWakaMonitoring', fn (User $user): bool => app(WakaMonitoringPolicy::class)->exportMonitoring($user));
    }
}
