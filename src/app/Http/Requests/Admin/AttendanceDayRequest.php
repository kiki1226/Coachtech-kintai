<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 出勤・退勤（HH:MM）。どちらか入れたらもう一方も必須、退勤は出勤より後
            'clock_in_at'   => ['nullable', 'date_format:H:i', 'required_with:clock_out_at'],
            'clock_out_at'  => ['nullable', 'date_format:H:i', 'after:clock_in_at', 'required_with:clock_in_at'],

            // 休憩1：開始は出勤以降、終了は開始より後かつ退勤以内、開始を入れたら終了必須
            'break_started_at' => ['nullable', 'date_format:H:i', 'after_or_equal:clock_in_at'],
            'break_ended_at'   => ['nullable', 'date_format:H:i', 'after:break_started_at', 'before_or_equal:clock_out_at', 'required_with:break_started_at'],

            // 休憩2：上と同じ
            'break2_started_at' => ['nullable', 'date_format:H:i', 'after_or_equal:clock_in_at'],
            'break2_ended_at'   => ['nullable', 'date_format:H:i', 'after:break2_started_at', 'before_or_equal:clock_out_at', 'required_with:break2_started_at'],

            // 備考
            'note' => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in_at.required_with'  => '出勤と退勤は両方入力してください。',
            'clock_out_at.required_with' => '出勤と退勤は両方入力してください。',
            'clock_out_at.after'         => '出勤時間もしくは、退勤時間が不適切な値です。',

            'break_started_at.after_or_equal' => '休憩時間が不適切な値です。',
            'break_ended_at.required_with'    => '休憩終了時刻を入力してください。',
            'break_ended_at.after'            => '休憩終了は休憩開始より後にしてください。',
            'break_ended_at.before_or_equal'  => '休憩時間もしくは、退勤時間が不適切な値です。',

            'break2_started_at.after_or_equal' => '休憩時間が不適切な値です。',
            'break2_ended_at.required_with'    => '休憩終了時刻を入力してください。',
            'break2_ended_at.after'            => '休憩終了は休憩開始より後にしてください。',
            'break2_ended_at.before_or_equal'  => '休憩時間もしくは、退勤時間が不適切な値です。',

            '*.date_format' => ':attribute は HH:MM の形式で入力してください。',
            'note.required' => '備考を記入してください。',
            'note.max'      => '備考は200文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'clock_in_at'        => '出勤時刻',
            'clock_out_at'       => '退勤時刻',
            'break_started_at'   => '休憩開始時刻',
            'break_ended_at'     => '休憩終了時刻',
            'break2_started_at'  => '休憩2開始時刻',
            'break2_ended_at'    => '休憩2終了時刻',
            'note'               => '備考',
        ];
    }

    /**
     * 別名パラメータを正式名に寄せる
     * - 旧UI/旧管理画面由来の name を全部吸収
     */
    protected function prepareForValidation(): void
    {
        $in = $this->all();

        $map = [
            'clock_in'     => 'clock_in_at',
            'clock_out'    => 'clock_out_at',
            'break_start'  => 'break_started_at',   // 旧UI
            'break_end'    => 'break_ended_at',
            'break1_start' => 'break_started_at',   // 旧admin
            'break1_end'   => 'break_ended_at',
            'break2_start' => 'break2_started_at',
            'break2_end'   => 'break2_ended_at',
        ];

        $merge = [];
        foreach ($map as $from => $to) {
            if (!$this->filled($to) && $this->filled($from)) {
                $merge[$to] = $in[$from];
            }
        }
        if ($merge) {
            $this->merge($merge);
        }
    }

}
