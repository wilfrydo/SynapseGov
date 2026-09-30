<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountAndAttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_account_cannot_log_in(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@example.com',
            'password' => bcrypt('rahasia123'),
            'role' => 'citizen',
            'is_active' => false,
        ]);

        $this->post('/login', ['email' => 'nonaktif@example.com', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_active_account_can_log_in(): void
    {
        User::factory()->create([
            'email' => 'aktif@example.com',
            'password' => bcrypt('rahasia123'),
            'role' => 'citizen',
            'is_active' => true,
        ]);

        $this->post('/login', ['email' => 'aktif@example.com', 'password' => 'rahasia123']);

        $this->assertAuthenticated();
    }

    public function test_account_deactivated_mid_session_is_signed_out(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['role' => 'citizen', 'is_active' => true]);

        $this->actingAs($user)->get('/citizen/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())->get('/citizen/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_uploaded_evidence_is_stored_outside_the_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $department = Department::factory()->create(['code' => 'SEC1']);
        $citizen = User::factory()->create(['role' => 'citizen']);

        $this->actingAs($citizen)->post('/citizen/reports', [
            'title' => 'Jalan berlubang',
            'description' => 'Lubang besar di depan pasar',
            'category' => 'infrastruktur',
            'department_id' => $department->id,
            'priority' => 'medium',
            'attachments' => [UploadedFile::fake()->image('bukti.jpg')],
        ])->assertRedirect(route('citizen.dashboard'));

        $stored = Report::firstOrFail()->attachments[0];

        $this->assertStringStartsWith('attachments/reports/', $stored);
        Storage::disk('local')->assertExists($stored);
        Storage::disk('public')->assertMissing($stored);
    }

    public function test_audit_log_survives_deleting_the_user_who_acted(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $report = Report::factory()->create(['ticket_no' => 'RPT-20261001-AUD001']);

        $log = AuditLog::create([
            'auditable_type' => Report::class,
            'auditable_id' => $report->id,
            'user_id' => $staff->id,
            'event' => 'started_work',
        ]);

        $staff->delete();

        $this->assertNotNull($log->fresh());
        $this->assertNull($log->fresh()->user_id);
    }
}
