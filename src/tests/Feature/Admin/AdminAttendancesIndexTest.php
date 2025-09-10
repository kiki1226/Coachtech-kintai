<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAttendancesIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $overrides = []): User
    {
        $attrs = array_merge([
            'name'              => '管理者A',
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

    private function makeUser(string $name, string $email): User
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

    private function url(?string $date = null): string
    {
        // /admin/attendances?date=YYYY-MM-DD
        $base = route('admin.attendances.index', absolute: false) ?? '/admin/attendances';
        return $date ? $base.'?date='.$date : $base;
    }

    private function assertSeesDate(string $html, string $ymd): void
    {
        $c  = Carbon::parse($ymd)->locale('ja');
        $cands = [
            $c->toDateString(),                   // 2025-09-09
            $c->isoFormat('YYYY年M月D日'),        // 2025年9月9日
            $c->isoFormat('YYYY年M月D日 (ddd)'),  // 2025年9月9日 (火) など
            $c->isoFormat('M/D(ddd)'),            // 9/9(火)
        ];
        foreach ($cands as $s) {
            if (str_contains($html, $s)) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail('画面に日付が見つかりません: '.$ymd);
    }

    /** 2) 日付指定なしでアクセスすると今日が表示される */
    public function test_shows_today_when_no_date_param(): void
    {
        // 今日を固定（JST想定）
        Carbon::setTestNow(Carbon::create(2025, 9, 9, 10, 0, 0, 'Asia/Tokyo'));

        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $res = $this->get($this->url());
        $res->assertOk();

        $this->assertSeesDate($res->getContent(), now('Asia/Tokyo')->toDateString());
    }

    /** 1) 指定日の全ユーザーの勤怠が表示される */
    public function test_lists_all_users_attendance_for_the_day(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $date = '2025-09-08';

        $u1 = $this->makeUser('田中 太郎', 'taro@example.com');
        $u2 = $this->makeUser('山田 花子', 'hanako@example.com');

        Attendance::factory()->create([
            'user_id'      => $u1->id,
            'work_date'    => $date,
            'clock_in_at'  => $date.' 09:00:00',
            'clock_out_at' => $date.' 18:00:00',
        ]);
        Attendance::factory()->create([
            'user_id'      => $u2->id,
            'work_date'    => $date,
            'clock_in_at'  => $date.' 08:30:00',
            'clock_out_at' => $date.' 17:15:00',
        ]);

        $res = $this->get($this->url($date));
        $res->assertOk();

        $html = $res->getContent();

        // 指定日が表示される
        $this->assertSeesDate($html, $date);

        // 全ユーザー分が載っている（名前と時刻の一部で確認）
        $this->assertStringContainsString('田中 太郎', $html);
        $this->assertTrue(str_contains($html, '09:00') || str_contains($html, '9:00'));

        $this->assertStringContainsString('山田 花子', $html);
        $this->assertTrue(str_contains($html, '08:30') || str_contains($html, '8:30'));
    }

    /** 3) 前日指定で前日の情報が表示される */
    public function test_prev_day_shows_previous_day_data(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $base = Carbon::create(2025, 9, 9, 10, 0, 0, 'Asia/Tokyo');
        $prev = $base->copy()->subDay()->toDateString(); // 2025-09-08

        $u = $this->makeUser('前日 ユーザー', 'prev@example.com');

        Attendance::factory()->create([
            'user_id'      => $u->id,
            'work_date'    => $prev,
            'clock_in_at'  => $prev.' 10:00:00',
            'clock_out_at' => $prev.' 19:00:00',
        ]);

        $res = $this->get($this->url($prev));
        $res->assertOk();

        $html = $res->getContent();
        $this->assertSeesDate($html, $prev);
        $this->assertStringContainsString('前日 ユーザー', $html);
        $this->assertTrue(str_contains($html, '10:00') || str_contains($html, '10:0'));
    }

    /** 4) 翌日指定で翌日の情報が表示される */
    public function test_next_day_shows_next_day_data(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $base = Carbon::create(2025, 9, 9, 10, 0, 0, 'Asia/Tokyo');
        $next = $base->copy()->addDay()->toDateString(); // 2025-09-10

        $u = $this->makeUser('翌日 ユーザー', 'next@example.com');

        Attendance::factory()->create([
            'user_id'      => $u->id,
            'work_date'    => $next,
            'clock_in_at'  => $next.' 07:45:00',
            'clock_out_at' => $next.' 16:30:00',
        ]);

        $res = $this->get($this->url($next));
        $res->assertOk();

        $html = $res->getContent();
        $this->assertSeesDate($html, $next);
        $this->assertStringContainsString('翌日 ユーザー', $html);
        $this->assertTrue(str_contains($html, '07:45') || str_contains($html, '7:45'));
    }
}
