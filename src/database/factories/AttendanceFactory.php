<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $tz   = config('app.timezone', 'Asia/Tokyo');
        $date = Carbon::parse($this->faker->date(), $tz)->startOfDay();

        // まずは最低限の必須だけ
        return [
        'user_id'          => User::factory(),
        'work_date'        => $this->faker->date('Y-m-d'),
        'clock_in_at'      => null,   // ★ 必ず null
        'clock_out_at'     => null,   // ★ 必ず null
        'break_started_at' => null,   // ★ 必ず null
        'break_ended_at'   => null,   // ★ 必ず null
            // 他のカラムがあれば適宜
        ];

        // 便利用にデフォルトの時刻も入れておく（必要に応じてテスト側で上書きOK）
        $in  = (clone $date)->setTime(9, 0);
        $bS  = (clone $date)->setTime(12, 0);
        $bE  = (clone $date)->setTime(13, 0);
        $out = (clone $date)->setTime(18, 0);

        $data['clock_in_at']      = $in;
        $data['break_started_at'] = $bS;
        $data['break_ended_at']   = $bE;
        $data['clock_out_at']     = $out;

        // 存在するカラムだけ追加する
        if (Schema::hasColumn('attendances', 'break_minutes')) {
            $data['break_minutes'] = 60;
        }
        if (Schema::hasColumn('attendances', 'work_minutes')) {
            $data['work_minutes'] = 8 * 60;
        }
        if (Schema::hasColumn('attendances', 'break2_started_at')) {
            $data['break2_started_at'] = null;
        }
        if (Schema::hasColumn('attendances', 'break2_ended_at')) {
            $data['break2_ended_at'] = null;
        }

        return $data;
    }
}
