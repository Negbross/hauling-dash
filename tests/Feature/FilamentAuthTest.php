<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('filament login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
});

test('filament register screen can be rendered', function () {
    $response = $this->get('/admin/register');

    $response->assertOk();
});

test('guest is redirected to filament login when accessing admin dashboard', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

test('authenticated user can visit filament admin dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});

test('user can authenticate via filament login component using fortify logic', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password123',
            'remember' => true,
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
});

test('filament login rejects invalid credentials via fortify logic', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'wrongpassword',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

test('user can register via filament register component using fortify logic', function () {
    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Mining Operator',
            'email' => 'operator@hauling.test',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertDatabaseHas('users', [
        'email' => 'operator@hauling.test',
        'name' => 'Mining Operator',
    ]);

    $user = User::where('email', 'operator@hauling.test')->first();
    expect($user)->not->toBeNull();
    // Verify Fortify CreateNewUser created personal team!
    expect($user->currentTeam)->not->toBeNull();
    expect($user->personalTeam()->name)->toBe("Mining Operator's Team");

    $this->assertAuthenticatedAs($user);
});

test('/login redirects to filament login page', function () {
    $response = $this->get('/login');

    $response->assertRedirect('/admin/login');
});

test('/register redirects to filament register page', function () {
    $response = $this->get('/register');

    $response->assertRedirect('/admin/register');
});

test('/dashboard redirects to filament admin dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/admin');
});

test('fortify login.store authenticates user and redirects to admin dashboard', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('fortify register.store creates user and team and redirects to admin dashboard', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Coal Hauler',
        'email' => 'hauler@hauling.test',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/admin');

    $user = User::where('email', 'hauler@hauling.test')->first();
    expect($user)->not->toBeNull();
    expect($user->currentTeam)->not->toBeNull();
    expect($user->personalTeam()->name)->toBe("Coal Hauler's Team");
    $this->assertAuthenticatedAs($user);
});

