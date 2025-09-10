<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** 1) 登録完了イベント/再送で VerifyEmail が送られる */
    public function test_registered_event_sends_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        // 登録完了時に発火するイベントを直接発火（登録画面の細部に依存しない）
        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** 2) 誘導画面からの再送が成功する（通知が送られる&レスポンス正しい） */
    public function test_verification_notice_and_resend_sends_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user);

        // 誘導画面が開ける
        $this->get(route('verification.notice'))->assertOk();

        // 誘導画面から「再送」押下を再現（back() なので referer を付ける）
        $this->from(route('verification.notice'))
             ->post(route('verification.send'))
             ->assertRedirect(route('verification.notice'))
             ->assertSessionHas('success'); // ルート実装どおり

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** 3) 署名付きURLで認証が完了し、勤怠登録画面にリダイレクトする */
    public function test_signed_verification_link_completes_and_redirects(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user);

        // 署名付きURLを作成（あなたの routes/web.php の定義に合わせる）
        $verifyUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($verifyUrl)
             ->assertRedirect(route('attendance.register'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
