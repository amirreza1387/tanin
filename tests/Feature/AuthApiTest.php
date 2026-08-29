<?php

use App\Enums\Role;
use App\Models\User;

it('registers, logs in, reads the current user, and logs out', function (): void {
    $payload = [
        'name' => 'کاربر آزمایشی',
        'email' => 'user@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $register = $this->postJson('/api/v1/auth/register', $payload);
    $register->assertCreated()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.role', Role::USER->value);

    $token = $register->json('data.token');
    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'user@example.com');

    $this->postJson('/api/v1/auth/login', [
        'email' => $payload['email'],
        'password' => $payload['password'],
    ])->assertOk();

    $this->withToken($token)->postJson('/api/v1/auth/logout')
        ->assertOk();
});

it('returns Persian validation errors for invalid registration', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'not-an-email',
        'password' => 'short',
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['data', 'meta', 'errors'])
        ->assertJsonPath('errors.name.0', 'وارد کردن نام الزامی است.');
});

it('rejects invalid credentials', function (): void {
    User::factory()->create(['email' => 'user@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ])->assertUnauthorized()
        ->assertJsonPath('errors.message', 'ایمیل یا گذرواژه نادرست است.');
});

it('revokes all tokens when the password changes', function (): void {
    $user = User::factory()->create(['password' => 'old-password']);
    $currentToken = $user->createToken('current')->plainTextToken;
    $otherToken = $user->createToken('other')->plainTextToken;

    $this->withToken($currentToken)->putJson('/api/v1/auth/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    $this->app['auth']->forgetGuards();
    $this->withToken($currentToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->withToken($otherToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
});
