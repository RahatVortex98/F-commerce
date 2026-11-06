<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not create the predictable test account in production', function () {
    $this->app['env'] = 'production';

    (new DatabaseSeeder)->run();

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
