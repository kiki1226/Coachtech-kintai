<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginValidationTest extends TestCase
{
    use RefreshDatabase;

    /** ① メールアドレスが未記入の場合、バリデーションエラーになる */
    public function test_email_is_required(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email'    => '',
            'password' => 'password', // 適当な値
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest(); // 未ログインのまま
    }

    /** ② パスワードが未記入の場合、バリデーションエラーになる */
    public function test_password_is_required(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email'    => 'user@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['password']);
        $this->assertGuest();
    }

    /** ③ 登録内容と一致しない場合、バリデーションエラー（email）になる */
    public function test_invalid_credentials_show_error(): void
    {
        // 正しいユーザーを用意
        $user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // 間違ったパスワードでログインを試みる
        $response = $this->from('/login')->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        // Fortify は「一致しない場合」も email 側にエラーを付与する
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }
}
