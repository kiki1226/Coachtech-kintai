<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class AttendanceRequestSeeder extends Seeder
{
    public function run(): void
    {
        // 1) 日付カラムの自動判定
        $dateCol = collect(['from_at','to_at','target_date','date','work_date','target_day'])
            ->first(fn($c) => Schema::hasColumn('requests', $c));

        if (!$dateCol) {
            throw new \RuntimeException(
                'requests テーブルに日付カラムが見つかりません（from_at / to_at / target_date / date / work_date / target_day のいずれかが必要）'
            );
        }

        // 2) type カラムの許可値を判定（マイグレーション仕様に合わせてフォールバック）
        $typeAllowed = [];
        try {
            $colType = Schema::getColumnType('requests', 'type');
        } catch (\Throwable $e) {
            $colType = 'string';
        }

        if ($colType === 'integer') {
            $typeAllowed = [1, 2];
        } else {
            // あなたのマイグレーションで許可している文字列をここに合わせる
            $typeAllowed = ['attendance_correction', 'leave'];
        }

        if (count($typeAllowed) === 1) {
            $typeAllowed[] = $typeAllowed[0];
        }
        [$TYPE_A, $TYPE_B] = array_values($typeAllowed);

        // 3) ユーザーを確保（なければ1人作る）
        $user = User::first() ?? User::factory()->create();

        $when = Carbon::today();

        // 4) サンプル1件を挿入
        $payload = [
            'user_id'       => $user->id,
            'attendance_id' => null,
            'type'          => $TYPE_A,
            'status'        => 'pending',
            'reason'        => 'サンプル申請',
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        if (in_array($dateCol, ['from_at','to_at'])) {
            if (Schema::hasColumn('requests','from_at')) $payload['from_at'] = $when;
            if (Schema::hasColumn('requests','to_at'))   $payload['to_at']   = $when;
        } else {
            $payload[$dateCol] = $when;
        }
        DB::table('requests')->insert($payload);

        // 5) 追加で3件ほど
        $now = now();

        $dateColsFor = function(string $date) use ($dateCol) {
            if (in_array($dateCol, ['from_at','to_at'])) {
                return array_filter([
                    'from_at' => Schema::hasColumn('requests','from_at') ? $date : null,
                    'to_at'   => Schema::hasColumn('requests','to_at')   ? $date : null,
                ], fn($v) => !is_null($v));
            }
            return [$dateCol => $date];
        };

        $rows = [
            array_merge([
                'user_id'    => $user->id,
                'type'       => $TYPE_A,
                'reason'     => '遅延のため',
                'status'     => 'pending',
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now->copy()->subDays(3),
            ], $dateColsFor('2025-09-01')),

            array_merge([
                'user_id'    => $user->id,
                'type'       => $TYPE_B,
                'reason'     => '通院のため',
                'status'     => 'approved',
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ], $dateColsFor('2025-09-02')),

            array_merge([
                'user_id'    => $user->id,
                'type'       => $TYPE_A,
                'reason'     => '私用のため',
                'status'     => 'pending',
                'created_at' => $now->copy()->subDay(),
                'updated_at' => $now->copy()->subDay(),
            ], $dateColsFor('2025-09-03')),
        ];

        DB::table('requests')->insert($rows);
    }
}
