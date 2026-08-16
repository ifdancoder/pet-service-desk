<?php

use App\Models\User;

test('a user can exchange valid credentials for a token', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['token']]);
});

test('invalid credentials are rejected', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $response = $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable();
});

test('a authenticated user can revoke their current token', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password')]);

    $token = $this->postJson('/api/v1/auth/tokens', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->json('data.token');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/auth/tokens/current');

    $response->assertNoContent();
    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('revoking a token requires authentication', function () {
    $this->deleteJson('/api/v1/auth/tokens/current')->assertUnauthorized();
});
