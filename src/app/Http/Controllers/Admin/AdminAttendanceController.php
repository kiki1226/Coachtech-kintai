<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;  
use App\Http\Requests\Admin\AttendanceDayRequest;     

class AdminAttendanceController extends Controller
{
    /** 指定日の全スタッフ勤怠一覧 */
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Tokyo');

        // 表示する日付
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'), $tz)->startOfDay()
            : now($tz)->startOfDay();

        // その日の勤怠を一括取得（user_id で引けるようにキー化）
        $attendances = Attendance::whereDate('work_date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        // 表示するスタッフ一覧（必要なら絞り込みや並び替えを）
        $users = User::orderBy('name')->get();

        $rows = [];
        foreach ($users as $user) {
            $att = $attendances->get($user->id);

            $rows[] = [
                'id'    => $user->id,
                'name'  => $user->name,
                'start' => $att?->clock_in_at?->timezone($tz)->format('H:i') ?? '—',
                'end'   => $att?->clock_out_at?->timezone($tz)->format('H:i') ?? '—',
                'break' => $att?->break_hm ?? '00:00',   // ← モデルのアクセサ
                'total' => $att?->total_hm ?? '00:00',   // ← モデルのアクセサ
            ];
        }

        return view('admin.attendances.index', [
            'target'   => $date,
            'prevDate' => $date->copy()->subDay()->toDateString(),
            'nextDate' => $date->copy()->addDay()->toDateString(),
            'rows'     => $rows,
        ]);
    }

    /** ユーザー別：指定月の一覧（雛形） */
    public function user(Request $request, User $user)
    {
        // ① 月処理（YYYY-MM、既定は今月）
        $ym        = $request->query('month', now()->format('Y-m'));
        $month     = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $start     = $month->copy()->startOfMonth();
        $end       = $month->copy()->endOfMonth();
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        // ② 日付カラム（work_date / date）を自動判定
        $dateKey = Schema::hasColumn('attendances', 'work_date') ? 'work_date' : 'date';

        // ③ まとめて取得 → 日付で引けるようにマップ化
        $atts = Attendance::where('user_id', $user->id)
            ->whereDate($dateKey, '>=', $start->toDateString())
            ->whereDate($dateKey, '<=', $end->toDateString())
            ->get()
            ->keyBy(fn($a) => Carbon::parse($a->$dateKey)->toDateString());

        // ④ 1ヶ月分の行を生成
        $rows = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $att = $atts->get($d->toDateString());

            $startStr = optional($att?->clock_in_at)->format('H:i') ?? '';
            $endStr   = optional($att?->clock_out_at)->format('H:i') ?? '';

            // ★ここを自前計算 → アクセサ利用に変更
            $breakStr = $att?->break_hm ?? '0:00';
            $totalStr = ($att && $att->clock_in_at && $att->clock_out_at)
                ? $att->total_hm
                : '0:00';

            $rows[] = [
                'date'  => $d->toDateString(),
                'label' => $d->format('m/d') . '('. $this->wdayJa($d) .')',
                'start' => $startStr,
                'end'   => $endStr,
                'break' => $breakStr,
                'total' => $totalStr,
            ];
        }

        // ⑤ CSV 出力（任意）
        if ($request->query('export') === 'csv') {
            $filename = "attendance_{$user->id}_{$month->format('Y_m')}.csv";
            $headers  = ['Content-Type' => 'text/csv; charset=UTF-8'];
            $callback = function() use ($rows, $user, $month) {
                $out = fopen('php://output', 'w');
                // Excel で文字化けしないように BOM
                fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($out, ["{$user->name} さんの勤怠", $month->format('Y年n月')]);
                fputcsv($out, ['日付','出勤','退勤','休憩','合計']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['label'],$r['start'],$r['end'],$r['break'],$r['total']]);
                }
                fclose($out);
            };
            return response()->streamDownload($callback, $filename, $headers);
        }

        return view('admin.attendances.user', [
            'user'      => $user,
            'month'     => $month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'rows'      => $rows,
        ]);
    }

    /* ---------- ヘルパー ---------- */

    private function wdayJa(Carbon $d): string
    {
        // 日月火水木金土
        $w = ['日','月','火','水','木','金','土'];
        return $w[$d->dayOfWeek];
    }

    private function safeDiffMins($s, $e): int
    {
        if (!$s || !$e) return 0;
        $ss = $s instanceof Carbon ? $s : Carbon::parse($s);
        $ee = $e instanceof Carbon ? $e : Carbon::parse($e);
        if ($ee->lessThanOrEqualTo($ss)) return 0;
        return $ee->diffInMinutes($ss);
    }

    private function hhmm(int $m): string
    {
        $m = max(0, $m);
        return sprintf('%d:%02d', intdiv($m, 60), $m % 60);
    }

    private function calcBreakMins(?Attendance $att): int
    {
        if (!$att) return 0;

        $m = 0;
        $m += $this->safeDiffMins($att->break_started_at ?? null, $att->break_ended_at ?? null);

        if (Schema::hasColumn('attendances','break2_started_at') &&
            Schema::hasColumn('attendances','break2_ended_at')) {
            $m += $this->safeDiffMins($att->break2_started_at ?? null, $att->break2_ended_at ?? null);
        }

        // ★時刻が入っていない時は break_minutes を採用
        if ($m === 0 && isset($att->break_minutes)) {
            $m = (int) $att->break_minutes;
        }
        return $m;
    }

    /** ユーザー別：指定日の詳細 */
    public function showDay(Request $request, User $user, string $date)
    {
        $target = Carbon::parse($date)->startOfDay();

        // その日のレコード（無ければ空のオブジェクトを用意）
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $target->toDateString())
            ->first();

        return view('admin.attendances.show', compact('user','attendance','target'));
    }

    /** ユーザー別：指定日の勤怠情報更新*/
    public function updateDay(AttendanceDayRequest $request, \App\Models\User $user, string $date)
    {
        $tz     = config('app.timezone', 'Asia/Tokyo');
        $target = \Carbon\Carbon::createFromFormat('Y-m-d', $date, $tz)->startOfDay();

        // 文字列 "HH:MM" や ISO を Carbon に。最初に見つかったキーを使う
        $getTime = function (array $keys) use ($request, $target, $tz) {
            foreach ($keys as $k) {
                // filled() で空文字は除外（＝nullで保存）
                if (!$request->filled($k)) continue;
                $v = $request->input($k);
                if (preg_match('/^\d{1,2}:\d{2}$/', $v)) {
                    return \Carbon\Carbon::parse($target->toDateString().' '.$v, $tz);
                }
                return \Carbon\Carbon::parse($v, $tz);
            }
            return null;
        };

        $payload = [
            // 出勤・退勤（別名たくさん受ける）
            'clock_in_at'        => $getTime(['clock_in_at','start','start_at','clock_in']),
            'clock_out_at'       => $getTime(['clock_out_at','end','end_at','clock_out']),

            // 休憩1（よくある別名を全部拾う）
            'break_started_at'   => $getTime([
                'break_started_at','break_start','break_s','rest_start','rest_s','break1_started_at','break1_start','break1_s'
            ]),
            'break_ended_at'     => $getTime([
                'break_ended_at','break_end','break_e','rest_end','rest_e','break1_ended_at','break1_end','break1_e'
            ]),

            // 休憩2（使っていれば）
            'break2_started_at'  => $getTime([
                'break2_started_at','break2_start','break2_s','rest2_start','rest2_s'
            ]),
            'break2_ended_at'    => $getTime([
                'break2_ended_at','break2_end','break2_e','rest2_end','rest2_e'
            ]),

            'note'               => $request->input('note'),
        ];

        \DB::transaction(function () use ($user, $target, $payload) {
            $attendance = \App\Models\Attendance::where('user_id', $user->id)
                ->whereDate('work_date', $target->toDateString())
                ->first();

            if (!$attendance) {
                $attendance = new \App\Models\Attendance([
                    'user_id'   => $user->id,
                    'work_date' => $target->copy()->startOfDay(),
                ]);
            }

            // null だけ除外（'0' などは消さない）
            $filtered = array_filter($payload, fn($v) => !is_null($v));
            $attendance->fill($filtered)->save();
        });

        return redirect()
            ->route('admin.attendances.index', ['date' => $target->toDateString()])
            ->with('success', '勤怠を更新しました。');
    }


    /** 分を HH:MM 表示に */
    private function mm(int $mins): string
    {
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return sprintf('%02d:%02d', $h, $m);
    }


    /** 最初に存在するカラム値を返す */
    private function col(Attendance $att, array $names)
    {
        foreach ($names as $n) {
            if (isset($att->$n) && $att->$n) return $att->$n;
        }
        return null;
    }

}
