<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Define ADMIN_EMAIL y ADMIN_PASSWORD antes de crear el administrador.');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrador EcoCiudad'),
                'password' => Hash::make($password),
                'rol' => 'administrador',
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
