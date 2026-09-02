<?php

namespace App\Providers;

use App\Admin\AccountingReport;
use App\Admin\Dashboard;
use App\Admin\Report;
use App\Admin\Settings;
use App\Models\StockMovement;
use App\Models\User;
use App\Policies\AccountingReportPolicy;
use App\Policies\DashboardPolicy;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingsPolicy;
use App\Policies\StockPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadDomainMigrations();
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    protected function loadDomainMigrations(): void
    {
        $domains = [
            '01_core',
            '02_organization',
            '03_customers',
            '04_catalog',
            '05_inventory',
            '06_purchasing',
            '07_sales',
            '08_manufacturing',
            '09_accounting',
            '10_cms',
        ];

        foreach ($domains as $domain) {
            $this->loadMigrationsFrom(database_path("migrations/{$domain}"));
        }
    }

    protected function configureAuthorization(): void
    {
        Gate::policy(Dashboard::class, DashboardPolicy::class);
        Gate::policy(AccountingReport::class, AccountingReportPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Settings::class, SettingsPolicy::class);
        Gate::policy(StockMovement::class, StockPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        Gate::before(function (?User $user): ?bool {
            if ($user?->is_super_admin) {
                return true;
            }

            return null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
