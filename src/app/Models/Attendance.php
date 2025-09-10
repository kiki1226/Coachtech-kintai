<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AttendanceChange; 
use App\Models\AttendanceRequest;

class Attendance extends Model
{
    use HasFactory;

    /**
     * DBに実在するカラムのみを列挙
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'break_started_at',
        'break_ended_at',
        'break2_started_at',
        'break2_ended_at',
        'break_minutes',   // 休憩合計（分）を数値で持つ場合
        'work_minutes',    // 実働（分）を数値で持つ場合（任意）
        'note',
    ];

    /**
     * 型キャスト
     */
    protected $casts = [
        'work_date'         => 'date',
        'clock_in_at'       => 'datetime',
        'clock_out_at'      => 'datetime',
        'break_started_at'  => 'datetime',
        'break_ended_at'    => 'datetime',
        'break2_started_at' => 'datetime',
        'break2_ended_at'   => 'datetime',
    ];

    /**
     * 画面用の付加プロパティ
     * 例: $attendance->break_hm / ->work_hm / ->total_hm
     */
    protected $appends = ['break_hm', 'work_hm', 'total_hm'];

    /**
     * リレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /* ────── 小さいユーティリティ ────── */

    private function minutesToHm(int $mins): string
    {
        if ($mins <= 0) {
            return '00:00';
        }
        return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
    }

    private function span(?CarbonInterface $start, ?CarbonInterface $end): int
    {
        if (!$start || !$end) return 0;
        // true にしておくと負値にならない（並べ替えミスでも安全）
        return (int) $end->diffInMinutes($start, true);
    }

    public function pendingChange()
    {
        // requestsテーブルに attendance_id がある設計ならこちらでOK
        return $this->hasOne(AttendanceRequest::class, 'attendance_id', 'id')
            ->where('status', 'applying')
            ->latestOfMany();
    }

    /**
     * 休憩合計（分）を“単一の入口”として取得
     * 1) breaks() リレーションがあれば合計
     * 2) break_minutes カラムがあればそれ
     * 3) break_* 時刻ペアの差分（2区間まで）
     */
    // 休憩合計（分）
    private function breakMinutes(): int
    {
        // ❶ breaks リレーションがある＆レコードがあればそれを合算（minutes or start/end）
        if (method_exists($this, 'breaks')) {
            try {
                $collection = $this->relationLoaded('breaks')
                    ? $this->getRelation('breaks')
                    : $this->breaks()->get(['start','end','minutes']);

                if ($collection && $collection->count()) {
                    $m = $collection->sum(function ($b) {
                        if (isset($b->minutes) && $b->minutes !== null) {
                            return (int) $b->minutes;
                        }
                        $s = optional($b->start) ? \Carbon\Carbon::parse($b->start) : null;
                        $e = optional($b->end)   ? \Carbon\Carbon::parse($b->end)   : null;
                        return ($s && $e) ? (int) $e->diffInMinutes($s, true) : 0;
                    });
                    return max(0, (int) $m);
                }
            } catch (\Throwable $e) {
                // 取得できなければ次の方法にフォールバック
            }
        }

        // ❷ 自分の時刻ペア（1回目/2回目）があればそれを合算
        $m = 0;
        if ($this->break_started_at && $this->break_ended_at) {
            $m += $this->span($this->break_started_at, $this->break_ended_at);
        }
        if ($this->break2_started_at && $this->break2_ended_at) {
            $m += $this->span($this->break2_started_at, $this->break2_ended_at);
        }
        if ($m > 0) return $m;

        // ❸ どちらも無ければ数値カラムを採用
        return (int) ($this->attributes['break_minutes'] ?? 0);
    }

    // 休憩 HH:MM（表示）
    public function getBreakHmAttribute(): string
    {
        return $this->minutesToHm($this->breakMinutes());
    }

    // 実働（休憩控除“前”） HH:MM
    public function getWorkHmAttribute(): string
    {
        if (!$this->clock_in_at || !$this->clock_out_at) return '00:00';
        $mins = $this->span($this->clock_in_at, $this->clock_out_at);
        return $this->minutesToHm($mins);
    }

    // 実働（休憩控除“後” / 合計） HH:MM
    public function getTotalHmAttribute(): string
    {
        if (!$this->clock_in_at || !$this->clock_out_at) return '00:00';
        $gross = $this->span($this->clock_in_at, $this->clock_out_at);
        $net   = max(0, $gross - $this->breakMinutes());
        return $this->minutesToHm($net);
    }

    /**
     * 編集画面で「時刻」から再計算して保存したいときに呼ぶヘルパ
     * - 休憩合計（分）を break_minutes へ
     * - work_minutes を持っているプロジェクトでは、実働（分）も更新
     */
    public function recalcTotalsFromTimes(): void
    {
        // 時刻ペアから休憩合計（分）
        $b  = $this->span($this->break_started_at,  $this->break_ended_at);
        $b += $this->span($this->break2_started_at, $this->break2_ended_at);

        $this->break_minutes = (int) $b;

        // 実働（分）を数値で持つ設計なら一緒に更新
        if (array_key_exists('work_minutes', $this->attributes)) {
            $work = 0;
            if ($this->clock_in_at && $this->clock_out_at) {
                $work = $this->span($this->clock_in_at, $this->clock_out_at);
            }
            $this->work_minutes = max(0, $work - $b);
        }
    }
}
