<?php

namespace Tests\Feature;


use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GameUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_manager_can_update_their_own_games()
    {
        $manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $game = Game::factory()->create([
            'manager' => $manager->id
        ]);

        $response = $this->actingAs($manager, 'sanctum')->put("/api/games/{$game->id}", [
            'name' => 'Updated Game',
            'description' => 'Updated Description',
            'price' => 20.00,
            'image' => 'updated-image.jpg',
            'youtube_url' => 'https://www.youtube.com/watch?v=raaandommvideeososidlmao'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Game updated successfully',
                'game' => [
                    'id' => $game->id,
                    'name' => 'Updated Game',
                    'description' => 'Updated Description',
                    'manager' => $manager->id,
                    'price' => 20,
                    'image' => 'updated-image.jpg',
                    'youtube_url' => 'https://www.youtube.com/watch?v=raaandommvideeososidlmao',
                    'created_at' => $game->created_at->toJSON(),
                    'updated_at' => $game->updated_at->toJSON(),
                    'platforms' => [],
                    'genres' => [],
                    'cryptos' => []
                ],
                'platforms' => [],
                'genres' => [],
                'cryptos' => []
            ]);

    }

    public function test_a_manager_cannot_update_games_they_dont_manage()
    {
        $manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $other_manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $game = Game::factory()->create([
            'manager' => $manager->id
        ]);

        $response = $this->actingAs($other_manager, 'sanctum')->putJson("/api/games/{$game->id}", [
            'name' => 'I will try to update this game, so I could ruin the other manager\'s business',
            'description' => 'even this description',
            'price' => 59.99,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);
    }

    public function test_an_admin_can_update_any_game()
    {
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        $manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $game = Game::factory()->create([
            'manager' => $manager->id,  
        ]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/games/{$game->id}", [
            'name' => 'I can do anything',
            'description' => 'cuz I am an admin',
            'price' => 20.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Game updated successfully',
                'game' => [
                    'id' => $game->id,
                    'name' => 'I can do anything',
                    'description' => 'cuz I am an admin',
                    'manager' => $manager->id,
                    'price' => 20,
                    
                ]
            ]);
    }

    public function test_a_basic_user_cannot_update_games()
    {
        $basic_user = User::factory()->create([
            'role' => 'basic_user'
        ]);

        $manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $game = Game::factory()->create([
            'manager' => $manager->id
        ]);

        $response = $this->actingAs($basic_user, 'sanctum')->putJson("/api/games/{$game->id}", [
            'name' => 'I will try to update this game, but I shouldnt tho',
            'description' => 'even this description',
            'price' => 59.99,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized'
            ]);
    }

    public function test_any_user_cannot_update_a_non_existent_game()
    {
        $manager = User::factory()->create([
            'role' => 'manager'
        ]);

        $fakegameid = 69420;

        $response = $this->actingAs($manager, 'sanctum')->putJson("api/games/{$fakegameid}", [
            'name' => 'Shoot for the stars, it might hit a game',
            'description' => 'this is a description btw'
        ]);

        $response->assertStatus(404)->assertJson([
            'message'=> 'Game not found'
        ]);
    }
}
