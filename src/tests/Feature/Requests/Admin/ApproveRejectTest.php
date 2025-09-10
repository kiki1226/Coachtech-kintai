<?php

namespace Tests\Feature\Requests\Admin;

use App\Models\{User, Attendance, AttendanceRequest};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint; 
use Tests\TestCase;  

class ApproveRejectTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
        {
            parent::setUp();

            // テストDB（SQLite）のみに一時列を追加。既にあればスキップ。
            Schema::table('requests', function (Blueprint $table) {
                if (!Schema::hasColumn('requests', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('status');
                }
                if (!Schema::hasColumn('requests', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
                    // 外部キーは不要なら張らない（SQLite だと面倒なため）
                    // $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        }
    private function makeAdmin(): User {
        return User::factory()->create([
            'is_admin' => true,   // role制なら 'role' => 'admin'
        ]);
    }

    public function test_admin_can_approve()
    {
        $admin = $this->makeAdmin();
        $user  = User::factory()->create();
        $att   = Attendance::factory()->create(['user_id' => $user->id]);
        $req   = AttendanceRequest::factory()->create([
            'attendance_id' => $att->id,
            'user_id'       => $user->id,
            'status'        => AttendanceRequest::STATUS_PENDING ?? 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.approve', $req))
            ->assertRedirect();

        $this->assertEquals(
            AttendanceRequest::STATUS_APPROVED ?? 'approved',
            $req->fresh()->status
        );
    }

    public function test_non_admin_cannot_approve()
    {
        $user = User::factory()->create();
        $req  = AttendanceRequest::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.requests.approve', $req))
            ->assertForbidden();
    }
}
