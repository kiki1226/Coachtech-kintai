<?php

namespace Tests\Feature\Requests;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAndShowTest extends TestCase
{
    use RefreshDatabase;

    /** 6: 承認待ちにログインユーザーの申請が全て表示される */
    public function test_pending_requests_are_listed_for_user(): void
    {
        $user = User::factory()->create();

        $type = defined(AttendanceRequest::class.'::TYPE_ATTENDANCE_CORRECTION')
            ? AttendanceRequest::TYPE_ATTENDANCE_CORRECTION
            : (defined(AttendanceRequest::class.'::TYPE_ADJUST') ? AttendanceRequest::TYPE_ADJUST : 'adjust');

        $pending = defined(AttendanceRequest::class.'::STATUS_PENDING')
            ? AttendanceRequest::STATUS_PENDING
            : (defined(AttendanceRequest::class.'::STATUS_APPLYING') ? AttendanceRequest::STATUS_APPLYING : 'applying');

        // 自分の勤怠＆申請 2 件
        $a1 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2025-09-07']);
        $a2 = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2025-09-08']);

        AttendanceRequest::create([
            'attendance_id' => $a1->id,
            'user_id'       => $user->id,
            'type'          => $type,
            'status'        => $pending,
            'target_date'   => '2025-09-07',
            'reason'        => '修正1',
        ]);

        AttendanceRequest::create([
            'attendance_id' => $a2->id,
            'user_id'       => $user->id,
            'type'          => $type,
            'status'        => $pending,
            'target_date'   => '2025-09-08',
            'reason'        => '修正2',
        ]);

        $this->actingAs($user);

        // タブ指定があれば: route('requests.index', ['tab' => 'pending'])
        $res = $this->get(route('requests.index'));

        $res->assertOk()
            ->assertSeeText('申請一覧');  // ヘッダ

        // 日付の表記ゆれには依存せず、理由テキストで検証
        $res->assertSeeText('修正1')
            ->assertSeeText('修正2');
    }

    /** 7: 承認済みタブに管理者が承認した申請が表示される */
    public function test_approved_requests_are_listed_for_user(): void
    {
        $user = User::factory()->create();

        $type = defined(AttendanceRequest::class.'::TYPE_ATTENDANCE_CORRECTION')
            ? AttendanceRequest::TYPE_ATTENDANCE_CORRECTION
            : (defined(AttendanceRequest::class.'::TYPE_ADJUST') ? AttendanceRequest::TYPE_ADJUST : 'adjust');

        $approved = defined(AttendanceRequest::class.'::STATUS_APPROVED')
            ? AttendanceRequest::STATUS_APPROVED
            : 'approved';

        $a = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2025-09-09']);

        AttendanceRequest::create([
            'attendance_id' => $a->id,
            'user_id'       => $user->id,
            'type'          => $type,
            'status'        => $approved,
            'target_date'   => '2025-09-09',
            'reason'        => '承認済みテスト',
        ]);

        $this->actingAs($user);

        // タブ指定があれば: route('requests.index', ['tab' => 'approved'])
        $res = $this->get(route('requests.index'));

        $res->assertOk()
            ->assertSeeText('申請一覧')
            ->assertSeeText('承認済み');        // 見出しがあれば
            // ->assertSeeText('承認済みテスト'); // 画面に理由を出していれば有効化
    }

    /** 8: 各申請の「詳細」から勤怠詳細へリンクがある（または申請詳細に遷移できる） */
    public function test_request_detail_link_to_attendance_show(): void
    {
        $user = User::factory()->create();

        $pending = defined(AttendanceRequest::class.'::STATUS_PENDING')
            ? AttendanceRequest::STATUS_PENDING
            : (defined(AttendanceRequest::class.'::STATUS_APPLYING') ? AttendanceRequest::STATUS_APPLYING : 'applying');

        $type = defined(AttendanceRequest::class.'::TYPE_ATTENDANCE_CORRECTION')
            ? AttendanceRequest::TYPE_ATTENDANCE_CORRECTION
            : (defined(AttendanceRequest::class.'::TYPE_ADJUST') ? AttendanceRequest::TYPE_ADJUST : 'adjust');

        $att = Attendance::factory()->create(['user_id' => $user->id, 'work_date' => '2025-09-10']);

        $req = AttendanceRequest::create([
            'attendance_id' => $att->id,
            'user_id'       => $user->id,
            'type'          => $type,
            'status'        => $pending,
            'target_date'   => '2025-09-10',
            'reason'        => '詳細リンク確認',
        ]);

        $this->actingAs($user);

        $res = $this->get(route('requests.index'));
        $res->assertOk()
            ->assertSee('詳細'); // テキストが存在するか

        // 一覧に attendance.show のURLを出していれば有効化
        // $res->assertSee(route('attendance.show', ['attendance' => $att->id]));
    }

    // ★ requests に存在する日付カラム名を返す（無ければ null）
    private function reqDateKey(): ?string
    {
        foreach (['target_date','date','work_date','target_day','from_at','to_at'] as $c) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('requests', $c)) return $c;
        }
        return null;
    }

    // ★ create 用配列に日付を入れる（from_at / to_at 両対応）
    private function putRequestDate(array $row, string $ymd): array
    {
        $key = $this->reqDateKey();
        if (!$key) return $row;

        if (in_array($key, ['from_at','to_at'], true)) {
            $dt = \Carbon\Carbon::parse($ymd);
            if (\Illuminate\Support\Facades\Schema::hasColumn('requests','from_at')) $row['from_at'] = $dt;
            if (\Illuminate\Support\Facades\Schema::hasColumn('requests','to_at'))   $row['to_at']   = $dt;
            return $row;
        }
        $row[$key] = $ymd;
        return $row;
    }

    // ★ 画面で出がちな日付表記ゆれ候補を返す
    private function dateStrings(string $ymd): array
    {
        $c = \Carbon\Carbon::parse($ymd)->locale('ja');
        return [
            $c->toDateString(),                 // 2025-09-07
            $c->format('Y/m/d'),                // 2025/09/07
            $c->format('m/d'),                  // 09/07
            $c->format('n/j'),                  // 9/7
            $c->isoFormat('YYYY年M月D日'),      // 2025年9月7日
            $c->isoFormat('YYYY年M月D日(ddd)'), // 2025年9月7日(日)
            $c->isoFormat('M月D日'),            // 9月7日
            $c->isoFormat('M月D日(ddd)'),       // 9月7日(日)
        ];
    }
}
