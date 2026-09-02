<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Services\Shared\AuditService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_index_page_loads(): void
    {
        $user = $this->adminUser();
        $this->createLog($user, 'sale.completed');

        $response = $this->actingAs($user)->get(route('admin.audit-logs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/audit-logs/index')
            ->has('logs')
            ->has('summary')
            ->has('eventBreakdown')
            ->has('filters')
            ->has('filterOptions'));
    }

    public function test_audit_log_show_page_loads(): void
    {
        $user = $this->adminUser();
        $log = $this->createLog($user, 'sale.completed', [
            'invoice_number' => 'INV-001',
            'total' => 100,
        ]);

        $response = $this->actingAs($user)->get(route('admin.audit-logs.show', $log));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/audit-logs/show')
            ->where('auditLog.id', $log->id)
            ->where('auditLog.event', 'sale.completed')
            ->has('auditLog.changed_fields', 2));
    }

    public function test_audit_logs_can_filter_by_event(): void
    {
        $user = $this->adminUser();
        $this->createLog($user, 'sale.completed');
        $this->createLog($user, 'sale.voided');

        $response = $this->actingAs($user)->get(route('admin.audit-logs.index', [
            'event' => 'sale.voided',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.event', 'sale.voided')
            ->has('logs.data', 1)
            ->where('logs.data.0.event', 'sale.voided'));
    }

    /** @param  array<string, mixed>  $newValues */
    private function createLog(User $user, string $event, array $newValues = ['total' => 50]): AuditLog
    {
        return app(AuditService::class)->log(
            event: $event,
            newValues: $newValues,
            user: $user,
        );
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        return User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
