<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDateCalendarVisualTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['name' => 'Admin Test', 'status' => 'active']);
        $this->admin->assignRole('admin');

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin Test', 'status' => 'active']);
        $this->superAdmin->assignRole('super-admin');
    }

    public function test_all_seven_admin_report_pages_render_and_contain_date_inputs(): void
    {
        $urls = [
            route('admin.reports.attendance'),
            route('admin.reports.visits'),
            route('admin.reports.routes'),
            route('admin.reports.transactions'),
            route('admin.reports.sales-activities'),
            route('admin.reports.driver-routes'),
            route('admin.reports.driver-visits'),
        ];

        $this->actingAs($this->admin);

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $content = $response->getContent();

            // Verify date inputs exist in the form
            $this->assertStringContainsString('name="from_date"', $content);
            $this->assertStringContainsString('name="to_date"', $content);
            $this->assertStringContainsString('type="date"', $content);

            // Verify central styles exist in layout
            $this->assertStringContainsString('webkit-calendar-picker-indicator', $content);
            $this->assertStringContainsString('filter: invert(1)', $content);
        }
    }

    public function test_layout_contains_centralized_dark_and_light_calendar_picker_css(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.reports.attendance'));
        $content = $response->getContent();

        // Check CSS rule for webkit calendar indicator
        $this->assertStringContainsString('input[type="date"]::-webkit-calendar-picker-indicator', $content);
        $this->assertStringContainsString('cursor: pointer;', $content);
        $this->assertStringContainsString('.dark input[type="date"]::-webkit-calendar-picker-indicator', $content);
        $this->assertStringContainsString('filter: invert(1)', $content);
    }
}
