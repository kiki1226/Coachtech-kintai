<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => trim((string) $this->input('name')),
            'email' => trim((string) mb_convert_kana($this->input('email'), 'a')),
        ]);
    }

    public function rules(): array
    {
        return [
            // usersテーブルのカラム名は name（※あなたの環境の事実）
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            // confirmed で password_confirmation を自動チェック
        ];
    }

    /**
     * 日本語メッセージ（必要分だけでOK）
     */
    public function messages(): array
    {
        return [
            'email.unique'      => 'このメールアドレスは既に使用されています。',
            'email.email'       => 'メールアドレスを入力してください。',
            'name.required'     => 'お名前を入力してください。',
            'password.required' => 'パスワードを入力してください。',
            'password.min'      => 'パスワードは:min文字以上で入力してください。',
            'password.confirmed'=> 'パスワード（確認）と一致しません。',
        ];
    }

    /**
     * 属性名（:attribute の表示名を日本語化）
     */
    public function attributes(): array
    {
        return [
            'name'                  => '名前',
            'email'                 => 'メールアドレス',
            'password'              => 'パスワード',
            'password_confirmation' => 'パスワード（確認）',
        ];
    }
}
