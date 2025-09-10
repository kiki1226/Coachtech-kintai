<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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
            'clock_in'      => ['nullable', 'date_format:H:i'],
            'clock_out'     => ['nullable', 'date_format:H:i', 'after:clock_in'],

            // 休憩1：開始は任意、入れるなら出勤以降 / 終了は開始と退勤の間に
            'break1_start'  => ['nullable', 'date_format:H:i', 'after_or_equal:clock_in'],
            'break1_end'    => ['nullable', 'date_format:H:i', 'after:break1_start', 'before_or_equal:clock_out', 'required_with:break1_start'],

            // 休憩2（ある場合）
            'break2_start'  => ['nullable', 'date_format:H:i', 'after_or_equal:clock_in'],
            'break2_end'    => ['nullable', 'date_format:H:i', 'after:break2_start', 'before_or_equal:clock_out', 'required_with:break2_start'],

            // 備考は必須（要件通り）
            'note'          => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_out.after'           => '出勤時間もしくは、退勤時間が不適切な値です。',

            'break1_start.after_or_equal' => '休憩時間が不適切な値です。',
            'break1_end.required_with'    => '休憩終了時刻を入力してください。',
            'break1_end.after'            => '休憩終了は休憩開始より後にしてください。',
            'break1_end.before_or_equal'  => '休憩時間もしくは、退勤時間が不適切な値です。',

            'break2_start.after_or_equal' => '休憩時間が不適切な値です。',
            'break2_end.required_with'    => '休憩終了時刻を入力してください。',
            'break2_end.after'            => '休憩終了は休憩開始より後にしてください。',
            'break2_end.before_or_equal'  => '休憩時間もしくは、退勤時間が不適切な値です。',

            'note.required' => '備考を記入してください。',
            'note.max'      => '備考は200文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'clock_in'    => '出勤時刻',
            'clock_out'   => '退勤時刻',
            'break_start' => '休憩開始時刻',
            'break_end'   => '休憩終了時刻',
            'note'        => '備考',
        ];
    }
}
