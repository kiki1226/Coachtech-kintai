<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterScreenTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';
    private string $path = '/attendance/register'; // ルートが違う場合はここを変更

    /** 現在の（または指定の）日付が UI と同じ形式（Y-m-d）で view に渡される */
    public function test_date_string_matches_ui_format(): void
    {
        $user = User::factory()->create();

        // 固定日付にして検証（クエリ ?date= を渡せば register() 側でその日を採用）
        $ymd = '2025-09-08';
        $this->actingAs($user);

        $response = $this->get($this->path.'?date='.$ymd);

        $response
            ->assertStatus(200)
            ->assertViewIs('attendance.register')
            ->assertViewHas('day', $ymd);
    }

    /** ア：勤務外（当日のレコード無し） => state = before_clock_in */
    public function test_state_is_before_clock_in_when_no_record(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';

        $this->actingAs($user);

        // 当日の Attendance を作らない
        $response = $this->get($this->path.'?date='.$ymd);

        $response
            ->assertStatus(200)
            ->assertViewIs('attendance.register')
            ->assertViewHas('state', 'before_clock_in');
    }

    /** イ：出勤中（出勤はあるが退勤も休憩終了も無し） => state = after_clock_in */
    public function test_state_is_after_clock_in_when_clocked_in(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';

        Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => $ymd,
            'clock_in_at'  => Carbon::parse($ymd.' 09:00', $this->tz),
            'clock_out_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get($this->path.'?date='.$ymd);

        $response
            ->assertStatus(200)
            ->assertViewIs('attendance.register')
            ->assertViewHas('state', 'after_clock_in');
    }

    /** ウ：休憩中（休憩開始はあるが休憩終了無し） => state = on_break */
    public function test_state_is_on_break_when_break_started_and_not_ended(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';

        Attendance::create([
            'user_id'            => $user->id,
            'work_date'          => $ymd,
            'clock_in_at'        => Carbon::parse($ymd.' 09:00', $this->tz),
            'break_started_at'   => Carbon::parse($ymd.' 12:00', $this->tz),
            'break_ended_at'     => null,
            'clock_out_at'       => null,
        ]);

        $this->actingAs($user);

        $response = $this->get($this->path.'?date='.$ymd);

        $response
            ->assertStatus(200)
            ->assertViewIs('attendance.register')
            ->assertViewHas('state', 'on_break');
    }

    /** エ：退勤済（退勤が入っている） => state = after_clock_out */
    public function test_state_is_after_clock_out_when_clocked_out(): void
    {
        $user = User::factory()->create();
        $ymd  = '2025-09-08';

        Attendance::create([
            'user_id'      => $user->id,
            'work_date'    => $ymd,
            'clock_in_at'  => Carbon::parse($ymd.' 09:00', $this->tz),
            'clock_out_at' => Carbon::parse($ymd.' 18:00', $this->tz),
            // 休憩が入っていても退勤が最優先で after_clock_out になる前提
            'break_started_at' => Carbon::parse($ymd.' 12:00', $this->tz),
            'break_ended_at'   => Carbon::parse($ymd.' 12:45', $this->tz),
        ]);

        $this->actingAs($user);

        $response = $this->get($this->path.'?date='.$ymd);

        $response
            ->assertStatus(200)
            ->assertViewIs('attendance.register')
            ->assertViewHas('state', 'after_clock_out');
    }
}
