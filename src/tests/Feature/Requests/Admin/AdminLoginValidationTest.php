<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLoginValidationTest extends TestCase
{
    use RefreshDatabase;

    /** 管理者フラグ(またはrole)を付けたユーザーと通常ユーザーを作るためのヘルパ */
    private function makeUser(array $overrides = [], bool $asAdmin = false): User
    {
        $attrs = array_merge([
            'name'              => 'テストユーザー',
            'email'             => 'user@example.com',
            'password'          => Hash::make('password123'),
            'email_verified_at' => now(),   // 認証要求があっても通るように
        ], $overrides);

        // is_admin カラム or role カラム、どちらでも対応
        if ($asAdmin) {
            if (Schema::hasColumn('users', 'is_admin')) {
                $attrs['is_admin'] = 1;
            } elseif (Schema::hasColumn('users', 'role')) {
                $attrs['role'] = 'admin';
            }
        } else {
            if (Schema::hasColumn('users', 'is_admin')) {
                $attrs['is_admin'] = 0;
            } elseif (Schema::hasColumn('users', 'role')) {
                $attrs['role'] = 'user';
            }
        }

        return User::factory()->create($attrs);
    }

    /** 管理者ログイン POST の送信ヘルパ */
    private function postAdminLogin(array $payload = [])
    {
        $url = route('admin.login.post', absolute: false) ?? '/admin/login';
        return $this->from('/admin/login')->post($url, $payload);
    }

    /** 1) メールアドレスが未入力だとエラーになる */
    public function test_email_is_required_on_admin_login(): void
    {
        $res = $this->postAdminLogin([
            'email'    => '',
            'password' => 'password123',
        ]);

        $res->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email']);
    }

    /** 2) パスワードが未入力だとエラーになる */
    public function test_password_is_required_on_admin_login(): void
    {
        $res = $this->postAdminLogin([
            'email'    => 'admin@example.com',
            'password' => '',
        ]);

        $res->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['password']);
    }

    /** 3) 誤った資格情報だとエラーになる（email にエラー付与想定） */
    public function test_invalid_credentials_show_error_on_admin_login(): void
    {
        // 管理者ユーザーを用意（パスワードは correct-pass）
        $this->makeUser([
            'email'    => 'admin@example.com',
            'password' => Hash::make('correct-pass'),
        ], asAdmin: true);

        $res = $this->postAdminLogin([
            'email'    => 'admin@example.com',
            'password' => 'wrong-pass',
        ]);

        $res->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email']);
    }

    /** 4) 一般ユーザーは管理画面にログインできない（権限エラー） */
    public function test_non_admin_user_cannot_login_to_admin(): void
    {
        $this->makeUser([
            'email'    => 'user@example.com',
            'password' => Hash::make('password123'),
        ], asAdmin: false);

        $res = $this->postAdminLogin([
            'email'    => 'user@example.com',
            'password' => 'password123',
        ]);

        // 多くの実装で email にエラーを返す（「管理者権限がありません」など）
        $res->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email']);
        $this->assertGuest(); // 未ログインのまま
    }

    /** 5) 管理者は正しくログインでき、/admin 配下へ遷移する */
    public function test_admin_can_login_successfully(): void
    {
        $admin = $this->makeUser([
            'email'    => 'admin@example.com',
            'password' => Hash::make('password123'),
        ], asAdmin: true);

        $res = $this->postAdminLogin([
            'email'    => 'admin@example.com',
            'password' => 'password123',
        ]);

        // 認証済みであること
        $this->assertAuthenticatedAs($admin);

        // リダイレクト先が /admin 配下であることを緩く確認
        $res->assertStatus(302);
        $location = $res->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringContainsString('/admin', $location);

        // もし固定のルートに飛ばす実装ならこちらでもOK:
        // $res->assertRedirect(route('admin.dashboard'));
        // または $res->assertRedirect(route('admin.attendances.index'));
    }
}
