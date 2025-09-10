<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 管理者ミドルウェアで守られている前提。必要なら here で Gate 等も可
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'      => ['nullable', 'date_format:H:i'],
            'clock_out'     => ['nullable', 'date_format:H:i'],
            'break1_start'  => ['nullable', 'date_format:H:i'],
            'break1_end'    => ['nullable', 'date_format:H:i'],
            // 管理側は備考必須の前提（テスト⑤）
            'note'          => ['required', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.date_format'     => '出勤時刻はHH:MM形式で入力してください。',
            'clock_out.date_format'    => '退勤時刻はHH:MM形式で入力してください。',
            'break1_start.date_format' => '休憩開始はHH:MM形式で入力してください。',
            'break1_end.date_format'   => '休憩終了はHH:MM形式で入力してください。',
            'note.required'            => '備考を入力してください。',
        ];
    }

    /**
     * フィールド相互関係（時刻の前後）を最後に厳密チェック
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $tz   = config('app.timezone', 'Asia/Tokyo');
                $date = (string) $this->route('date'); // /admin/attendances/{user}/{date}

                // helper
                $toDT = function (?string $hm) use ($date, $tz): ?Carbon {
                    if (!$hm) return null;
                    return Carbon::createFromFormat('Y-m-d H:i', "$date $hm", $tz);
                };

                $in   = $toDT($this->input('clock_in'));
                $out  = $toDT($this->input('clock_out'));
                $bS   = $toDT($this->input('break1_start'));
                $bE   = $toDT($this->input('break1_end'));

                // ① 出勤 > 退勤 はエラー
                if ($in && $out && $in->gt($out)) {
                    $validator->errors()->add('clock_out', '退勤時刻は出勤時刻以降である必要があります。');
                }

                // ② 休憩開始 > 退勤 はエラー
                if ($bS && $out && $bS->gt($out)) {
                    $validator->errors()->add('break1_start', '休憩開始は退勤より前である必要があります。');
                }

                // ③ 休憩終了 > 退勤 はエラー
                if ($bE && $out && $bE->gt($out)) {
                    $validator->errors()->add('break1_end', '休憩終了は退勤より前である必要があります。');
                }
            }
        ];
    }
}
