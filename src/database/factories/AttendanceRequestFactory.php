<?php

namespace Database\Factories;

use App\Models\AttendanceRequest;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\AttendanceRequest> */
class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    public function definition(): array
    {
        // 関連する勤怠を生成
        $attendance = Attendance::factory()->create();

        $type = defined(AttendanceRequest::class.'::TYPE_ATTENDANCE_CORRECTION')
            ? AttendanceRequest::TYPE_ATTENDANCE_CORRECTION : 'adjust';

        $pending = defined(AttendanceRequest::class.'::STATUS_PENDING')
            ? AttendanceRequest::STATUS_PENDING : 'pending';

        return [
            'attendance_id' => $attendance->id,
            'user_id'       => $attendance->user_id,
            'type'          => $type,
            'status'        => $pending,
            'target_date'   => is_object($attendance->work_date)
                ? $attendance->work_date->format('Y-m-d')
                : $attendance->work_date,
            'reason'        => $this->faker->sentence(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }

    /** 任意：承認済みに切り替える状態 */
    public function approved(): static
    {
        $approved = defined(AttendanceRequest::class.'::STATUS_APPROVED')
            ? AttendanceRequest::STATUS_APPROVED : 'approved';

        return $this->state(fn() => ['status' => $approved]);
    }
}
