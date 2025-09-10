<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $registerUrl = '/register';

    /** @test 1: 名前が未記入の場合 */
    public function name_is_required(): void
    {
        $payload = [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors('name');
        $this->assertGuest(); // ログインされていない
    }

    /** @test 2: メールアドレスが未記入の場合 */
    public function email_is_required(): void
    {
        $payload = [
            'name' => '山田太郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test 3: パスワードが8文字未満の場合 */
    public function password_must_be_at_least_8_chars(): void
    {
        $payload = [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'short7',       // 7文字
            'password_confirmation' => 'short7',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test 4: パスワードと確認用が一致しない場合 */
    public function password_must_match_confirmation(): void
    {
        $payload = [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors('password'); // confirmed ルール違反
        $this->assertGuest();
    }

    /** @test 5: パスワードが未記入の場合 */
    public function password_is_required(): void
    {
        $payload = [
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    /** @test 6: 全て未入力の場合は各項目のバリデーションが表示される */
    public function all_fields_empty_show_validation_errors(): void
    {
        $payload = [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        $res = $this->from($this->registerUrl)->post($this->registerUrl, $payload);

        $res->assertRedirect($this->registerUrl);
        $res->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertGuest();
    }
}
