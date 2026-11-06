<?php

use App\Models\User;
use Database\Seeders\TemporaryAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates and updates the temporary administrator from configured credentials', function () {
    config([
        'temporary_admin.email' => 'admin@example.test',
        'temporary_admin.password' => 'admin-temp-pass1',
    ]);

    $seeder = new TemporaryAdminSeeder;
    $seeder->run();

    $admin = User::where('email', 'admin@example.test')->firstOrFail();
    $adminId = $admin->id;

    $this->assertModelExists($admin);
    expect(Hash::check('admin-temp-pass1', $admin->password))->toBeTrue();
    expect($admin->is_admin)->toBeTrue();

    config(['temporary_admin.password' => 'admin-temp-pass2']);
    $seeder->run();

    expect(User::where('email', 'admin@example.test')->value('id'))->toBe($adminId);
    expect(Hash::check('admin-temp-pass2', User::findOrFail($adminId)->password))->toBeTrue();
});

it('rejects invalid email configuration without creating a user', function () {
    config([
        'temporary_admin.email' => 'not-an-email',
        'temporary_admin.password' => 'admin-temp-pass1',
    ]);

    expect(fn () => (new TemporaryAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'TEMPORARY_ADMIN_EMAIL');

    $this->assertDatabaseCount('users', 0);
});

it('rejects a weak password without creating a user', function () {
    config([
        'temporary_admin.email' => 'admin@example.test',
        'temporary_admin.password' => 'short',
    ]);

    expect(fn () => (new TemporaryAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'TEMPORARY_ADMIN_PASSWORD');

    $this->assertDatabaseCount('users', 0);
});

it('rejects a missing password without creating a user', function () {
    config([
        'temporary_admin.email' => 'admin@example.test',
        'temporary_admin.password' => null,
    ]);

    expect(fn () => (new TemporaryAdminSeeder)->run())
        ->toThrow(RuntimeException::class, 'TEMPORARY_ADMIN_PASSWORD');

    $this->assertDatabaseCount('users', 0);
});
