<?php

declare(strict_types=1);

namespace App\Providers;

use App\Integrations\Dapodik\DapodikConnector;
use App\Integrations\Dapodik\UnavailableDapodikConnector;
use App\Integrations\Etatib\EtatibConnector;
use App\Integrations\Etatib\UnavailableEtatibConnector;
use App\Models\BkCase;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Policies\CasePolicy;
use App\Policies\TeacherAssignmentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DapodikConnector::class, UnavailableDapodikConnector::class);
        $this->app->bind(EtatibConnector::class, UnavailableEtatibConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(TeacherAssignment::class, TeacherAssignmentPolicy::class);
        Gate::policy(BkCase::class, CasePolicy::class);
    }
}
