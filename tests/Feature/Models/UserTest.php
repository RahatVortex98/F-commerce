<?php

use App\Models\User;
use Filament\Facades\Filament;

it('allows only the configured admin to access the production panel', function () {
    $this->app['env'] = 'production';
    config(['temporary_admin.email' => 'admin@example.test']);
    $panel = Filament::getPanel('bikrify');

    $admin = new User;
    $admin->email = 'admin@example.test';
    $admin->is_admin = true;
    $otherUser = new User;
    $otherUser->email = 'other@example.test';

    expect($admin->canAccessPanel($panel))->toBeTrue();
    expect($otherUser->canAccessPanel($panel))->toBeFalse();
});

it('allows only administrators to access the panel in local environments', function () {
    $this->app['env'] = 'local';
    config(['temporary_admin.email' => null]);
    $customer = new User;
    $admin = new User;
    $admin->is_admin = true;

    expect($customer->canAccessPanel(Filament::getPanel('bikrify')))->toBeFalse();
    expect($admin->canAccessPanel(Filament::getPanel('bikrify')))->toBeTrue();
});

it('does not grant panel access based on the configured admin email alone', function () {
    $this->app['env'] = 'production';
    config(['temporary_admin.email' => 'admin@example.test']);
    $user = new User;
    $user->email = 'admin@example.test';

    expect($user->canAccessPanel(Filament::getPanel('bikrify')))->toBeFalse();
});
