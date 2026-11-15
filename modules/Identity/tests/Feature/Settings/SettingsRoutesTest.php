<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('settings redirects to the profile settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings')
        ->assertRedirect(route('profile.edit'));
});

test('appearance settings page is displayed for verified users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/appearance'));
});

test('appearance settings page is not reachable for guests', function () {
    $this->get(route('appearance.edit'))
        ->assertRedirect(route('login'));
});

test('passkey well-known endpoint points to the security page', function () {
    $this->get('/.well-known/passkey-endpoints')
        ->assertOk()
        ->assertExactJson([
            'enroll' => route('security.edit'),
            'manage' => route('security.edit'),
        ]);
});

test('identity routes keep their names', function () {
    $names = [
        'home',
        'dashboard',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'security.edit',
        'user-password.update',
        'appearance.edit',
        'well-known.passkeys',
    ];

    foreach ($names as $name) {
        expect(Route::has($name))->toBeTrue("Route [{$name}] is missing.");
    }
});
