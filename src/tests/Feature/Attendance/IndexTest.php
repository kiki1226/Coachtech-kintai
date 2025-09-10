<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    /** 当月の任意日付で勤怠を1件作るヘルパ */
    private function makeAttendance(User $user, Carbon $ymd, string $in = '09:00', string $out = '18:00'): Attendance
    {
        $inAt  = $ymd->copy()->setTimezone($this->tz)->setTimeFromTimeString($in);
        $outAt = $ymd->copy()->setTimezone($this->tz)->setTimeFromTimeString($out);

        return Attendance::create([
            'user_id'       => $user->id,
            'work_date'     => $ymd->toDateString(),
            'clock_in_at'   => $inAt,
            'clock_out_at'  => $outAt,
            'break_minutes' => 0,
            'note'          => null,
        ]);
    }

    /** @test */
    public function index_shows_only_my_attendances_for_selected_month(): void
    {
        $this->freezeTime(); // Carbon::setTestNow 相当（Laravel 11）
        $today = now($this->tz);
        $monthStr = $today->format('Y-m');

        $me   = User::factory()->create();
        $else = User::factory()->create();

        // 自分の当月データ 2件
        $my1 = $this->makeAttendance($me,  $today->copy()->day(4),  '09:00', '18:00');
        $my2 = $this->makeAttendance($me,  $today->copy()->day(7),  '10:01', '19:31');

        // 他人の当月データ（表示されないはず）
        $other = $this->makeAttendance($else, $today->copy()->day(8), '07:00', '16:00');

        $this->actingAs($me);

        $res = $this->get(route('attendance.index', ['m' => $monthStr]))
            ->assertOk()
            ->assertViewIs('attendance.index');

        // 自分の出退勤時刻は見える
        $res->assertSee('09:00')->assertSee('18:00');
        $res->assertSee('10:01')->assertSee('19:31');

        // 他ユーザーの時刻は見えない
        $res->assertDontSee('07:00')->assertDontSee('16:00');
    }

    /** @test */
    public function index_without_query_param_defaults_to_current_month_heading(): void
    {
        // 2025-09 を仮定（任意）
        $base = Carbon::create(2025, 9, 7, 12, 0, 0, $this->tz);
        $this->travelTo($base);

        $me = User::factory()->create();
        $this->actingAs($me);

        $res = $this->get(route('attendance.index'))
            ->assertOk();

        // 見出しは YYYY年M月 で出ている想定（Blade: $base->isoFormat('YYYY年M月')）
        $res->assertSee('2025年9月', false);
    }

    /** @test */
    public function index_prev_month_navigation_shows_prev_month_data_and_heading(): void
    {
        $base = Carbon::create(2025, 9, 1, 0, 0, 0, $this->tz);
        $this->travelTo($base);

        $me = User::factory()->create();

        // 前月(2025-08)に1件
        $prev = $base->copy()->subMonth()->day(15);
        $this->makeAttendance($me, $prev, '08:30', '17:15');

        $this->actingAs($me);

        $res = $this->get(route('attendance.index', ['m' => $prev->format('Y-m')]))
            ->assertOk();

        // 見出しが前月
        $res->assertSee('2025年8月', false);

        // 前月データの時刻が見える
        $res->assertSee('08:30')->assertSee('17:15');
    }

    /** @test */
    public function index_next_month_navigation_shows_next_month_data_and_heading(): void
    {
        $base = Carbon::create(2025, 9, 1, 0, 0, 0, $this->tz);
        $this->travelTo($base);

        $me = User::factory()->create();

        // 翌月(2025-10)に1件
        $next = $base->copy()->addMonth()->day(3);
        $this->makeAttendance($me, $next, '11:11', '20:20');

        $this->actingAs($me);

        $res = $this->get(route('attendance.index', ['m' => $next->format('Y-m')]))
            ->assertOk();

        $res->assertSee('2025年10月', false);
        $res->assertSee('11:11')->assertSee('20:20');
    }

    /** @test */
    public function index_detail_link_destination_can_be_opened(): void
    {
        $this->freezeTime();
        $me = User::factory()->create();

        $att = $this->makeAttendance($me, now($this->tz)->copy()->day(6), '09:00', '18:00');

        $this->actingAs($me);

        // 一覧が開けること
        $this->get(route('attendance.index'))->assertOk();

        // 「詳細」リンクの遷移先（ここでは show ルート想定）が 200 で開けること
        $this->get(route('attendance.show', ['attendance' => $att->id]))
            ->assertOk()
            ->assertViewIs('attendance.show');
    }
}
