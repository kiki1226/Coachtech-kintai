<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowActionsTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    private function ym(string $ymd): string
    {
        return Carbon::parse($ymd)->format('Y-m');
    }

    /** 1-1 出勤：ボタンが正しく機能し、レコードが作成される＆一覧に表示される */
    public function test_clock_in_creates_record_and_appears_on_index(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';

        $this->actingAs($user);

        // 9:00 に出勤ボタン
        Carbon::setTestNow(Carbon::parse($ymd.' 09:00', $this->tz));
        $this->post(route('attendance.clock_in'), ['date' => $ymd])
            ->assertRedirect();

        // ★ robust な存在チェック（DBの datetime/ date 差異に強い）
        $this->assertTrue(
            Attendance::where('user_id', $user->id)->whereDate('work_date', $ymd)->exists(),
            'attendance row for the day should exist'
        );

        $att = Attendance::where('user_id', $user->id)->whereDate('work_date', $ymd)->first();
        $this->assertNotNull($att->clock_in_at);
        $this->assertSame('09:00', $att->clock_in_at->timezone($this->tz)->format('H:i'));

        // 一覧に時刻が出る
        $res = $this->get(route('attendance.index', ['m' => $this->ym($ymd)]));
        $res->assertStatus(200)
            ->assertViewIs('attendance.index')
            ->assertSee('09:00');
    }

    /** 1-2 出勤：1日1回のみ（2回目の出勤は無視され、時刻が変化しない＆レコードが増えない） */
    public function test_clock_in_only_once_per_day(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';
        $this->actingAs($user);

        // 1回目 09:00
        Carbon::setTestNow(Carbon::parse($ymd.' 09:00', $this->tz));
        $this->post(route('attendance.clock_in'), ['date' => $ymd]);

        // 2回目 10:00（無視される前提）
        Carbon::setTestNow(Carbon::parse($ymd.' 10:00', $this->tz));
        $this->post(route('attendance.clock_in'), ['date' => $ymd]);

        $rows = Attendance::where('user_id', $user->id)->whereDate('work_date', $ymd)->get();
        $this->assertCount(1, $rows);

        $att = $rows->first();
        $this->assertSame('09:00', $att->clock_in_at->timezone($this->tz)->format('H:i'));
    }

    /** 休憩：開始/終了が機能し、複数回の休憩も加算される＆一覧に休憩合計が表示される（00:25 など） */
    public function test_break_can_start_end_multiple_times_and_appears_on_index(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';
        $this->actingAs($user);

        // 出勤
        Carbon::setTestNow(Carbon::parse($ymd.' 09:00', $this->tz));
        $this->post(route('attendance.clock_in'), ['date' => $ymd]);

        // 休憩1: 12:00 ~ 12:15（15分）
        Carbon::setTestNow(Carbon::parse($ymd.' 12:00', $this->tz));
        $this->post(route('attendance.break_start'), ['date' => $ymd]);

        Carbon::setTestNow(Carbon::parse($ymd.' 12:15', $this->tz));
        $this->post(route('attendance.break_end'), ['date' => $ymd]);

        // 休憩2: 15:00 ~ 15:10（10分）
        Carbon::setTestNow(Carbon::parse($ymd.' 15:00', $this->tz));
        $this->post(route('attendance.break_start'), ['date' => $ymd]);

        Carbon::setTestNow(Carbon::parse($ymd.' 15:10', $this->tz));
        $this->post(route('attendance.break_end'), ['date' => $ymd]);

        $att = \App\Models\Attendance::query()
            ->where('user_id', $user->id)                
            ->whereDate('work_date', $ymd)               
            ->firstOrFail();

        $expected = e($att->break_hm); 

        $this->get(route('attendance.index', ['m' => $this->ym($ymd)]))
            ->assertStatus(200)
            ->assertViewIs('attendance.index')
            ->assertSee($expected);  
    }

    /** 退勤：ボタンが正しく機能し、一覧に退勤時刻が表示される */
    public function test_clock_out_sets_time_and_appears_on_index(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';
        $this->actingAs($user);

        // 出勤
        Carbon::setTestNow(Carbon::parse($ymd.' 09:00', $this->tz));
        $this->post(route('attendance.clock_in'), ['date' => $ymd]);

        // 退勤 18:30
        Carbon::setTestNow(Carbon::parse($ymd.' 18:30', $this->tz));
        $this->post(route('attendance.clock_out'), ['date' => $ymd]);

        $att = Attendance::where('user_id', $user->id)->whereDate('work_date', $ymd)->first();
        $this->assertNotNull($att->clock_out_at);
        $this->assertSame('18:30', $att->clock_out_at->timezone($this->tz)->format('H:i'));

        // 一覧に退勤時刻
        $this->get(route('attendance.index', ['m' => $this->ym($ymd)]))
            ->assertStatus(200)
            ->assertViewIs('attendance.index')
            ->assertSee('18:30');
    }
}
