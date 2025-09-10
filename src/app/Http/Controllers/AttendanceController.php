<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use App\Http\Requests\AttendanceRequest as AttendanceFormRequest; // ← フォーム
use App\Models\AttendanceRequest as AttendanceChange;       


class AttendanceController extends Controller
{
    
    /** 出勤打刻画面（将来編集用） */
    public function create(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', now()->toDateString());

        // テストが使う work_date を第一に、他の名前も一応フォロー
        $attendance = Attendance::where('user_id', $user->id)
            ->where(function ($q) use ($date) {
                $q->whereDate('work_date', $date)
                  ->orWhereDate('date', $date)
                  ->orWhereDate('target_date', $date)
                  ->orWhereDate('day', $date);
            })
            ->latest('id')
            ->first();

        // 状態判定（テスト前提のカラム名）
        $clockIn  = filled(optional($attendance)->clock_in_at);
        $clockOut = filled(optional($attendance)->clock_out_at);
        $onBreak  = filled(optional($attendance)->break_started_at)
                 && empty(optional($attendance)->break_ended_at);

        // ★ Blade 側の分岐名に合わせる（*_clock_* 系）
        if ($clockOut) {
            $state = 'after_clock_out';
        } elseif ($clockIn && $onBreak) {
            $state = 'on_break';
        } elseif ($clockIn) {
            $state = 'after_clock_in';
        } else {
            $state = 'before_clock_in';
        }

        return view('attendance.register', [
            'attendance' => $attendance,
            'state'      => $state,
            'date'       => $date,
            'day'        => $date, // hidden に使う想定
        ]);
    }

    /** 手動登録の保存（将来の休暇申請等で使用予定） */
    public function store(Request $request)
    {
        // TODO: バリデーションと保存処理は後で実装
        return redirect()->route('attendance.index');
    }

    /** 勤怠一覧（当月） */
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Tokyo');
        $user = $request->user();

        $ymParam = $request->query('m') ?? $request->query('month');
        try {
            $base = $ymParam
                ? \Carbon\Carbon::createFromFormat('Y-m', $ymParam, $tz)->startOfMonth()
                : now($tz)->startOfMonth();
        } catch (\Throwable $e) {
            $base = now($tz)->startOfMonth();
        }

        $from = $base->copy()->startOfMonth()->toDateString();
        $to   = $base->copy()->endOfMonth()->toDateString();

        // 1) 当月のレコードを取得
        $records = \App\Models\Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$from, $to])
            ->get();

        // 2) 表示用に 休憩/合計 をここで計算して属性として持たせる
        $records->transform(function ($a) use ($tz) {
            $toDt = function ($v) use ($tz) {
                if (empty($v)) return null;
                return $v instanceof \Carbon\CarbonInterface ? $v->copy() : \Carbon\Carbon::parse($v, $tz);
            };

            $in  = $toDt($a->clock_in_at);
            $out = $toDt($a->clock_out_at);

            $b1s = $toDt($a->break_started_at);
            $b1e = $toDt($a->break_ended_at);
            $b2s = $toDt($a->break2_started_at ?? null);
            $b2e = $toDt($a->break2_ended_at   ?? null);

            $break = 0;
            if ($b1s && $b1e) $break += $b1e->diffInMinutes($b1s, false);
            if ($b2s && $b2e) $break += $b2e->diffInMinutes($b2s, false);
            $break = max(0, (int)$break);

            $fmt = function (int $mins) {
                return sprintf('%02d:%02d', intdiv($mins,60), $mins%60);
            };

            $work = 0;
            if ($in && $out) {
                $work = max(0, (int)($out->diffInMinutes($in, false) - $break));
            }

            // ← Blade からそのまま参照できるように一時属性として付与
            $a->break_hm = $fmt($break);
            $a->total_hm = $fmt($work);

            return $a;
        });

        // 3) 'Y-m-d' でキー化（あなたの Blade がその前提なので維持）
        $attendances = $records->keyBy(function ($a) use ($tz) {
            $d = $a->work_date instanceof \Carbon\CarbonInterface
                ? $a->work_date->copy()->timezone($tz)
                : \Carbon\Carbon::parse($a->work_date, $tz);
            return $d->toDateString();
        });

        return view('attendance.index', [
            'base'         => $base,
            'attendances'  => $attendances,
            'holidayDates' => [],
            'prevMonth'    => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth'    => $base->copy()->addMonth()->format('Y-m'),
        ]);
    }

    /** 詳細（将来拡張） */
    public function show(Request $request, int $id)
    {
        $attendance = Attendance::where('user_id', $request->user()->id)
            ->with(['user', 'pendingChange'])  // ← 申請1件を同時読込
            ->findOrFail($id);

        // 表示用にクローンを作り、申請値がある項目だけを上書き
        $display = clone $attendance;

        if ($attendance->pendingChange) {
            foreach ([
                'clock_in_at','clock_out_at',
                'break_started_at','break_ended_at',
                'break2_started_at','break2_ended_at',
                'note',
            ] as $f) {
                if (!is_null($attendance->pendingChange->$f)) {
                    $display->$f = $attendance->pendingChange->$f;
                }
            }
        }

        return view('attendance.show', [
            'attendance' => $attendance, // 元データ
            'display'    => $display,    // 申請で上書き済みの見せる用
            'pending'    => $attendance->pendingChange,
        ]);
    }

    public function edit(Request $request, Attendance $attendance)
    {
        // 自分の勤怠だけ
        abort_if($attendance->user_id !== $request->user()->id, 403);

        // 申請中の内容を読み込む
        $attendance->load('pendingChange');

        // フォーム表示用：申請中の値がある項目は上書き
        $form = clone $attendance;
        if ($attendance->pendingChange) {
            foreach ([
                'clock_in_at','clock_out_at',
                'break_started_at','break_ended_at',
                'break2_started_at','break2_ended_at',
                'note',
            ] as $f) {
                if (!is_null($attendance->pendingChange->$f)) {
                    $form->$f = $attendance->pendingChange->$f;
                }
            }
        }

        return view('attendance.edit', [
            'attendance' => $attendance, // 本体
            'form'       => $form,       // 表示用（申請値で上書き済み）
        ]);
    }

    // 時刻(H:i)をその日の日時に合成
    private function parseHmOnDate(?string $hm, Carbon $date, string $tz): ?Carbon {
        if (!$hm || !preg_match('/^\d{2}:\d{2}$/', $hm)) return null;
        [$H,$M] = explode(':', $hm);
        return (clone $date)->setTimezone($tz)->setTime((int)$H,(int)$M);
    }

    // 分解能を「分」で比較（どちらも null なら等価）
    private function timesEqual(?Carbon $a, ?Carbon $b, string $tz): bool {
        $fmt = fn($x)=> $x?->copy()->setTimezone($tz)->format('Y-m-d H:i');
        return $fmt($a) === $fmt($b);
    }

    public function update(AttendanceFormRequest $request, Attendance $attendance)
    {

        $from   = $request->input('from', 'attendance');
        $backYm = $request->input('back');
        $tz     = config('app.timezone', 'Asia/Tokyo');

        // 休憩2カラムの有無チェック
        $hasBreak2Start = Schema::hasColumn('attendances','break2_started_at');
        $hasBreak2End   = Schema::hasColumn('attendances','break2_ended_at');

        // ここは FormRequest がバリデーション済み
        $validated = $request->validated();

        // 勤務日の基準日付
        $baseDate = $attendance->work_date instanceof \Carbon\CarbonInterface
            ? $attendance->work_date->copy()
            : Carbon::parse($attendance->work_date, $tz);

        $toDT = function (?string $hhmm) use ($baseDate, $tz) {
            if (!$hhmm) return null;
            return Carbon::createFromFormat('Y-m-d H:i', $baseDate->format('Y-m-d').' '.$hhmm, $tz);
        };

        
        // カラムへの代入
        $attendance->clock_in_at      = $toDT($validated['clock_in']     ?? null);
        $attendance->clock_out_at     = $toDT($validated['clock_out']    ?? null);
        $attendance->break_started_at = $toDT($validated['break1_start'] ?? null);
        $attendance->break_ended_at   = $toDT($validated['break1_end']   ?? null);

        if ($hasBreak2Start) {
            $attendance->break2_started_at = $toDT($validated['break2_start'] ?? null);
        }
        if ($hasBreak2End) {
            $attendance->break2_ended_at = $toDT($validated['break2_end'] ?? null);
        }

        $attendance->note = $validated['note'] ?? $attendance->note;

            // 変更なし → 一覧へ戻す
            $dirty = ['clock_in_at','clock_out_at','break_started_at','break_ended_at','note'];
            if ($hasBreak2Start) $dirty[] = 'break2_started_at';
            if ($hasBreak2End)   $dirty[] = 'break2_ended_at';

            if (!$attendance->isDirty($dirty)) {
                return $from === 'requests'
                    ? redirect()->route('requests.index',   ['m' => $backYm])->with('info','変更はありませんでした。')
                    : redirect()->route('attendance.index', ['m' => $backYm])->with('info','変更はありませんでした。');
            }

            // 勤務日
            // 勤務日（申請対象日）
            $targetDate = $attendance->work_date instanceof \Carbon\CarbonInterface
                ? $attendance->work_date->toDateString()
                : \Carbon\Carbon::parse($attendance->work_date)->toDateString();

            $requestType = AttendanceChange::TYPE_ATTENDANCE_CORRECTION;

            DB::transaction(function () use ($attendance, $request, $targetDate, $requestType) {
                // 合計など再計算があれば実行
                if (method_exists($attendance, 'recalcTotals')) {
                    $attendance->recalcTotals();
                }
                $attendance->save();

                // ★ requests テーブルに存在する日付カラムを探す
                $dateCol = collect(['from_at','to_at','work_date','date','target_day','target_date'])
                    ->first(fn($c) => Schema::hasColumn('requests', $c));

                $reqDate = \Carbon\Carbon::parse($targetDate)->startOfDay();

                // 書き込む日付ペイロードを作る
                $datePayload = [];
                if (in_array($dateCol, ['from_at','to_at'])) {
                    // from/to 形式のときは両方あれば両方に入れる
                    if (Schema::hasColumn('requests', 'from_at')) $datePayload['from_at'] = $reqDate;
                    if (Schema::hasColumn('requests', 'to_at'))   $datePayload['to_at']   = $reqDate;
                } elseif ($dateCol) {
                    $datePayload[$dateCol] = $reqDate;
                }
                // ※ どのカラムも無ければ何も入れない（その設計なら日付無し申請）

                AttendanceChange::updateOrCreate(
                    [
                        'attendance_id' => $attendance->id,
                        'user_id'       => $request->user()->id,
                        'type'          => $requestType,
                        'status'        => AttendanceChange::STATUS_PENDING,
                    ],
                    array_merge([
                        // 理由はフォームの reason。無ければメモを流用など適宜
                        'reason' => $request->input('reason', $attendance->note),
                    ], $datePayload)
                );
            });

            // 申請中の詳細へ
            return redirect()->route('attendance.show', [
                'attendance' => $attendance->id,
                'm'          => $backYm,
            ])->with('success','変更を申請中にしました。');
    }
    
    public function register(Request $request)
    {
        $user = $request->user();
        $date = $request->query('date', now()->toDateString());

        // ← テストの前提: work_date / clock_in_at / clock_out_at / break_started_at / break_ended_at
        $att = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $date)
            ->latest('id')
            ->first();

        $clockIn  = filled(optional($att)->clock_in_at);
        $clockOut = filled(optional($att)->clock_out_at);
        $onBreak  = filled(optional($att)->break_started_at) && empty(optional($att)->break_ended_at);

        if ($clockOut) {
            $state = 'after_clock_out';
        } elseif ($clockIn && $onBreak) {
            $state = 'on_break';
        } elseif ($clockIn) {
            $state = 'after_clock_in';
        } else {
            $state = 'before_clock_in';
        }

        return view('attendance.register', [
            'state' => $state,
            'day'   => $date,   // Blade の hidden に使う
        ]);
    }

    /** 出勤打刻 */
    public function clockIn(Request $request)
    {
        $tz   = config('app.timezone', 'Asia/Tokyo');
        $userId = Auth::id();
        $day  = Carbon::parse($request->input('date', Carbon::now($tz)))->toDateString();

        DB::transaction(function () use ($userId, $day, $tz) {
            // その日の行をロック付きで取得
            $att = Attendance::where('user_id', $userId)
                ->whereDate('work_date', $day)
                ->lockForUpdate()
                ->first();

            if (!$att) {
                $att = Attendance::create([
                    'user_id'   => $userId,
                    'work_date' => $day,
                ]);
            }

            // 既に打刻済みなら何もしない（好みでバリデーション）
            if (!$att->clock_in_at) {
                $att->clock_in_at = Carbon::now($tz);
                $att->save();
            }
        });

        return back()->with('success', '出勤を記録しました');
    }

    public function clockOut(Request $request)
    {
        $tz   = config('app.timezone', 'Asia/Tokyo');
        $userId = Auth::id();
        $day  = Carbon::parse($request->input('date', Carbon::now($tz)))->toDateString();

        DB::transaction(function () use ($userId, $day, $tz) {
            $att = Attendance::where('user_id', $userId)
                ->whereDate('work_date', $day)
                ->lockForUpdate()
                ->first();

            if (!$att) {
                // 出勤が押されていなくても退勤で行を作るなら作成
                $att = Attendance::create([
                    'user_id'   => $userId,
                    'work_date' => $day,
                ]);
            }

            // 退勤打刻
            $att->clock_out_at = Carbon::now($tz);

            // ここで合計などの再計算をするならモデル側のメソッド呼び出し推奨
            // 例: $att->recalcTotals();
            $att->save();
        });

        return back()->with('success', '退勤を記録しました');
    }

    public function breakIn(Request $request)
    {
        $day = $this->normalizeDay($this->resolveDayFromRequest($request) ?? null);
        $att = $this->getByDayOrCreate($request, $day);

        if (empty($att->break_started_at) && empty($att->clock_out_at) && !empty($att->clock_in_at)) {
            $att->break_started_at = now('Asia/Tokyo');
            // $att->status = 'on_break'; ← 消す
            $att->save();
        }
        return back();
    }

    public function breakOut(Request $request)
    {
        $day = $this->normalizeDay($this->resolveDayFromRequest($request) ?? null);
        $att = $this->getByDayOrCreate($request, $day);

        if (!empty($att->break_started_at) && empty($att->break_ended_at)) {
            $att->break_ended_at = now('Asia/Tokyo');
            // $att->status = 'working'; ← 消す
            $att->save();
        }
        return back();
    }


    
    /** 登録 */
    private function redirectBackList(string $from, ?string $ym)
    {
        if ($from === 'requests') {
            return redirect()->route('requests.index', ['m' => $ym]);
        }
        return redirect()->route('attendance.index', ['m' => $ym]);
    }

    /*** 休憩開始 */
    public function breakStart(Request $request)
    {
        $day = $this->resolveDayFromRequest($request);

        DB::transaction(function () use ($day) {
            $attendance = Attendance::where('user_id', Auth::id())
                ->whereDate('work_date', $day)
                ->first();

            if (!$attendance || is_null($attendance->clock_in_at)) {
                abort(422, '先に出勤を打刻してください。');
            }
            if (!is_null($attendance->break_started_at) && is_null($attendance->break_ended_at)) {
                return;
            }
            $attendance->break_started_at = now();
            $attendance->break_ended_at   = null;
            $attendance->save();
        });

        return redirect()->route('attendance.register', ['date' => $day])
            ->with('success', '休憩を開始しました。');
    }

    /*** 休憩終了*/
    public function breakEnd(Request $request)
    {
        $day = $this->resolveDayFromRequest($request);

        DB::transaction(function () use ($day) {
            $attendance = Attendance::where('user_id', Auth::id())
                ->whereDate('work_date', $day)
                ->first();

            if (!$attendance || is_null($attendance->clock_in_at)) {
                abort(422, '先に出勤を打刻してください。');
            }
            if (is_null($attendance->break_started_at) || !is_null($attendance->break_ended_at)) {
                return;
            }

            $attendance->break_ended_at = now();
            $attendance->break_minutes +=
                Carbon::parse($attendance->break_started_at)->diffInMinutes($attendance->break_ended_at);

            $attendance->save();
        });

        return redirect()->route('attendance.register', ['date' => $day])
            ->with('success', '休憩を終了しました。');
    }

    private function resolveDayFromRequest(Request $request): string
    {
        // POST(date) > GET(date) > 今日 の優先順
        $raw = $request->input('date', $request->query('date'));
        return $raw ? Carbon::parse($raw)->toDateString() : now()->toDateString();
    }

    private function normalizeDay($raw): string
    {
        try {
            if ($raw instanceof Carbon) {
                return $raw->setTimezone('Asia/Tokyo')->toDateString();
            }
            if (is_string($raw) && trim($raw) !== '') {
                return Carbon::parse($raw, 'Asia/Tokyo')->toDateString();
            }
        } catch (\Throwable $e) {
            // パース失敗は現在日でフォールバック
        }
        return now('Asia/Tokyo')->toDateString();
    }

    // 追加：その日の勤怠を取得（なければ作成）
    private function getByDayOrCreate(Request $request, ?string $ymd = null): \App\Models\Attendance
    {
        $day = $ymd ?? now('Asia/Tokyo')->toDateString();

        $att = \App\Models\Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', $day)
            ->first();

        if (!$att) {
            $att = \App\Models\Attendance::create([
                'user_id'   => Auth::id(),
                'work_date' => $day,
            ]);
        }
        return $att;
    }
}
