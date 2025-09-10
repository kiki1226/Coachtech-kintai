<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function guest_is_redirected_from_home_to_login(): void
    {
        $this->get('/')               // ゲストでアクセス
             ->assertStatus(302)      // 302 でOK
             ->assertRedirect('/login'); // Fortify のログインに飛ぶ想定
    }
}
