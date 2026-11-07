<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('does not create the predictable test account in production', function () {
    $this->app['env'] = 'production';

    (new DatabaseSeeder)->run();

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'admin@fcommerce.demo']);
});

it('creates the development dashboard account in local environments', function () {
    $this->app['env'] = 'local';
    config([
        'temporary_admin.email' => 'admin@fcommerce.demo',
        'temporary_admin.password' => 'Bikrify-Local-2026!',
    ]);

    (new DatabaseSeeder)->run();

    $admin = User::where('email', 'admin@fcommerce.demo')->firstOrFail();

    expect($admin->is_admin)->toBeTrue();
    expect(Hash::check('Bikrify-Local-2026!', $admin->password))->toBeTrue();
});
