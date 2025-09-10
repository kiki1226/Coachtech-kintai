<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_open_home(): void
    {
        $user = User::factory()->create();

        // 必要なら followingRedirects() で最終画面まで追う
        $this->actingAs($user)
             ->get('/')               // ここがダッシュボードへ 302 -> 200 のはず
             ->assertStatus(302);     // or ->assertOk() / ->followingRedirects()->assertOk()
    }
}
