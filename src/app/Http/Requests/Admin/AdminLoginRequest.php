<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;               
use Illuminate\Support\Facades\Hash;               
use Illuminate\Validation\ValidationException;     
use App\Models\User;                        

class AdminLoginRequest extends FormRequest
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
            'email' => trim((string) mb_convert_kana($this->input('email'), 'a')),
        ]);
    }

    public function rules(): array
    {
        // ローカルは dns を外す（本番だけ付けたいなら app()->isProduction() 判定で）
        return [
            'email'    => ['required', 'string', 'email:filter', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'メールアドレス', 'password' => 'パスワード'];
    }

    public function messages(): array
    {
        return [
            'email.required'    => ':attributeを入力してください。',
            'email.email'       => ':attributeの形式が正しくありません。',
            'password.required' => ':attributeを入力してください。',
            'password.min'      => ':attributeは8文字以上で入力してください。',
        ];
    }

    // ここでログインまで実施
    public function authenticate(): void
    {
        $email    = (string) $this->input('email');
        $password = (string) $this->input('password');
        $remember = (bool) $this->input('remember', false);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        $isAdmin = (bool)($user->is_admin ?? 0) || ((string)($user->role ?? '') === 'admin');
        if (!$isAdmin) {
            throw ValidationException::withMessages([
                'email' => '管理者権限がありません。',
            ]);
        }

        Auth::login($user, $remember);
    }

}
