<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAttendanceShowAndUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** 管理者を作成（is_admin or role=admin のどちらでも対応） */
    private function makeAdmin(array $overrides = []): User
    {
        $attrs = array_merge([
            'name'              => '管理者',
            'email'             => 'admin@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),
        ], $overrides);

        if (Schema::hasColumn('users', 'is_admin')) {
            $attrs['is_admin'] = 1;
        } elseif (Schema::hasColumn('users', 'role')) {
            $attrs['role'] = 'admin';
        }

        return User::factory()->create($attrs);
    }

    /** 一般ユーザーを作成 */
    private function makeStaff(string $name, string $email): User
    {
        $attrs = [
            'name'              => $name,
            'email'             => $email,
            'password'          => Hash::make('pass-123456'),
            'email_verified_at' => now(),
        ];

        if (Schema::hasColumn('users', 'is_admin')) {
            $attrs['is_admin'] = 0;
        } elseif (Schema::hasColumn('users', 'role')) {
            $attrs['role'] = 'user';
        }

        return User::factory()->create($attrs);
    }

    /** 画面に日付が（表記ゆれに耐えて）出ているか */
    private function assertSeesDate(string $html, string $ymd): void
    {
        $c  = Carbon::parse($ymd)->locale('ja');
        $cands = [
            $c->toDateString(),
            $c->isoFormat('YYYY年M月D日'),
            $c->isoFormat('YYYY年M月D日 (ddd)'),
            $c->isoFormat('M/D(ddd)'),
        ];
        foreach ($cands as $s) {
            if (str_contains($html, $s)) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail('画面に日付が見つかりません: '.$ymd);
    }

    /** 1) 勤怠詳細画面に選択したユーザー／日付／時刻が表示される */
    public function test_show_displays_selected_attendance(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff('対象 太郎', 'taisho@example.com');
        $date = '2025-09-12';

        Attendance::factory()->create([
            'user_id'          => $staff->id,
            'work_date'        => $date,
            'clock_in_at'      => $date.' 09:00:00',
            'clock_out_at'     => $date.' 18:00:00',
            'break_started_at' => $date.' 12:00:00',
            'break_ended_at'   => $date.' 13:00:00',
            'note'             => 'メモあり',
        ]);

        $this->actingAs($admin);

        $res = $this->get(route('admin.attendances.show', [
            'user' => $staff->id,
            'date' => $date,
        ]));

        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('対象 太郎', $html);
        $this->assertSeesDate($html, $date);
        $this->assertTrue(str_contains($html, '09:00') || str_contains($html, '9:00'));
        $this->assertTrue(str_contains($html, '18:00') || str_contains($html, '18:0'));
        $this->assertTrue(str_contains($html, '12:00') || str_contains($html, '12:0'));
        $this->assertTrue(str_contains($html, '13:00') || str_contains($html, '13:0'));
        $this->assertStringContainsString('メモあり', $html);
    }

    /** 2) 出勤 > 退勤 はエラー */
    public function test_update_rejects_when_clock_in_after_clock_out(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff('A ユーザー', 'a@example.com');
        $date  = '2025-09-10';
        Attendance::factory()->create(['user_id' => $staff->id, 'work_date' => $date]);

        $this->actingAs($admin);

        $res = $this->from(route('admin.attendances.show', ['user' => $staff->id, 'date' => $date]))
            ->put(route('admin.attendances.update', ['user' => $staff->id, 'date' => $date]), [
                // ← 送信は alias でもOK（FormRequest側でマッピングする）
                'clock_in'     => '18:10',
                'clock_out'    => '08:15',
                'break1_start' => null,
                'break1_end'   => null,
                'note'         => '理由',
            ]);

        // エラーフィールド名は正式名で判定される
        $res->assertInvalid(['clock_out_at']);
    }

    /** 3) 休憩開始 > 退勤 はエラー */
    public function test_update_rejects_when_break_start_after_clock_out(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff('B ユーザー', 'b@example.com');
        $date  = '2025-09-11';
        Attendance::factory()->create(['user_id' => $staff->id, 'work_date' => $date]);

        $this->actingAs($admin);

        $res = $this->from(route('admin.attendances.show', ['user' => $staff->id, 'date' => $date]))
            ->put(route('admin.attendances.update', ['user' => $staff->id, 'date' => $date]), [
                'clock_in'     => '09:00',
                'clock_out'    => '18:00',
                'break1_start' => '19:00',
                'break1_end'   => '19:30',
                'note'         => '理由',
            ]);

        $res->assertInvalid(['break_ended_at']);
    }

    /** 4) 休憩終了 > 退勤 はエラー */
    public function test_update_rejects_when_break_end_after_clock_out(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff('C ユーザー', 'c@example.com');
        $date  = '2025-09-12';
        Attendance::factory()->create(['user_id' => $staff->id, 'work_date' => $date]);

        $this->actingAs($admin);

        $res = $this->from(route('admin.attendances.show', ['user' => $staff->id, 'date' => $date]))
            ->put(route('admin.attendances.update', ['user' => $staff->id, 'date' => $date]), [
                'clock_in'     => '09:00',
                'clock_out'    => '18:00',
                'break1_start' => '12:00',
                'break1_end'   => '19:00',
                'note'         => '理由',
            ]);

        $res->assertInvalid(['break_ended_at']);
    }

    /** 5) 備考が必須（未入力はエラー）※実装が必須ルールの前提 */
    public function test_update_requires_note(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff('D ユーザー', 'd@example.com');
        $date  = '2025-09-13';
        Attendance::factory()->create(['user_id' => $staff->id, 'work_date' => $date]);

        $this->actingAs($admin);

        $res = $this->from(route('admin.attendances.show', ['user' => $staff->id, 'date' => $date]))
            ->put(route('admin.attendances.update', ['user' => $staff->id, 'date' => $date]), [
                'clock_in'     => '09:00',
                'clock_out'    => '18:00',
                'break1_start' => '12:00',
                'break1_end'   => '13:00',
                'note'         => '',
            ]);

        $res->assertInvalid(['note']);
    }
}
