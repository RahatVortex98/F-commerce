<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class TemporaryAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('temporary_admin.email');
        $password = config('temporary_admin.password');

        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Set TEMPORARY_ADMIN_EMAIL to a valid email address before running this seeder.');
        }

        if (! is_string($password) || mb_strlen($password) < 16) {
            throw new RuntimeException('Set TEMPORARY_ADMIN_PASSWORD to a password of at least 16 characters before running this seeder.');
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Temporary Store Administrator',
                'password' => $password,
            ],
        );

        $admin->forceFill(['is_admin' => true])->save();
    }
}
