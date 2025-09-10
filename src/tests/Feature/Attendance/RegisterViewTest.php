<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterViewTest extends TestCase
{
    use RefreshDatabase;

    private string $tz = 'Asia/Tokyo';

    // ---------- helpers ----------
    /** HTMLに指定ルートの<form action="...">があるかを判定（絶対/相対どちらでもOK） */
    private function hasFormAction(string $html, string $routeName): bool
    {
        $url  = route($routeName);
        $path = parse_url($url, PHP_URL_PATH) ?? $url; // 念のため

        // <form ... action="http://localhost/attendance/clock-in"> も
        // <form ... action="/attendance/clock-in"> も拾う
        $pattern = '/<form[^>]+action=["\'][^"\']*' . preg_quote($path, '/') . '["\'][^>]*>/u';
        return preg_match($pattern, $html) === 1;
    }

    private function assertState(string $html, string $expected): void
    {
        // NBSP を通常スペースに
        $html = str_replace("\xC2\xA0", ' ', $html);

        // data-state = '...' / "..."、前後空白も許容
        $pattern = '/data-state\s*=\s*(["\'])' . preg_quote($expected, '/') . '\1/u';

        $this->assertMatchesRegularExpression(
            $pattern,
            $html,
            "Expected data-state=\"{$expected}\" to be present."
        );
    }


    private function assertHasAction(string $html, string $routeName): void
    {
        $this->assertTrue(
            $this->hasFormAction($html, $routeName),
            "Expected form action for route [{$routeName}] to exist."
        );
    }

    private function assertNotHasAction(string $html, string $routeName): void
    {
        $this->assertFalse(
            $this->hasFormAction($html, $routeName),
            "Expected form action for route [{$routeName}] to be absent."
        );
    }

    // ---------- tests ----------

    /** 出勤前：clock-in のフォームだけが見える */
    public function test_shows_before_clock_in_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-08 09:00', $this->tz));
        $user = User::factory()->create();

        $res  = $this->actingAs($user)->get(route('attendance.register', ['date' => '2025-09-08']));
        $res->assertOk();

        $html = $res->getContent();

        $this->assertHasAction($html, 'attendance.clock_in');
        $this->assertNotHasAction($html, 'attendance.clock_out');
        $this->assertNotHasAction($html, 'attendance.break_start');
        $this->assertNotHasAction($html, 'attendance.break_end');
    }

    // 出勤後
    public function test_shows_after_clock_in_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-08 10:00', $this->tz));
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'     => $user->id,
            'work_date'   => '2025-09-08',
            'clock_in_at' => Carbon::parse('2025-09-08 09:00', $this->tz),
            // break_* と clock_out_at は無し
        ]);

        $res  = $this->actingAs($user)->get(route('attendance.register', ['date' => '2025-09-08']));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertState($html, 'after_clock_in');
    }

    // 休憩中
    public function test_shows_on_break_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-08 12:10', $this->tz));
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'          => $user->id,
            'work_date'        => '2025-09-08',
            'clock_in_at'      => Carbon::parse('2025-09-08 09:00', $this->tz),
            'break_started_at' => Carbon::parse('2025-09-08 12:00', $this->tz),
            'break_ended_at'   => null,
        ]);

        $res  = $this->actingAs($user)->get(route('attendance.register', ['date' => '2025-09-08']));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertState($html, 'on_break');
    }

    // 退勤後
    public function test_shows_after_clock_out_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-08 18:30', $this->tz));
        $user = User::factory()->create();

        Attendance::factory()->create([
            'user_id'      => $user->id,
            'work_date'    => '2025-09-08',
            'clock_in_at'  => Carbon::parse('2025-09-08 09:00', $this->tz),
            'clock_out_at' => Carbon::parse('2025-09-08 18:00', $this->tz),
        ]);

        $res  = $this->actingAs($user)->get(route('attendance.register', ['date' => '2025-09-08']));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertState($html, 'after_clock_out');
        $this->assertStringContainsString('お疲れ様でした。', $html);
    }

    /** ヘッダーの日付/時刻表示（now固定） */
    public function test_header_shows_frozen_now(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-08 09:34', $this->tz));
        $user = User::factory()->create();

        $res  = $this->actingAs($user)->get(route('attendance.register', ['date' => '2025-09-08']));
        $res->assertOk();

        $html = $res->getContent();
        $this->assertStringContainsString('2025年9月8日', $html);
        $this->assertStringContainsString('09:34', $html);
    }
}
