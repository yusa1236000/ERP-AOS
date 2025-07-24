<?php
// database/seeders/CreateInitialUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateInitialUserSeeder extends Seeder
{
    public function run()
    {
        $user = User::find(1);

        if ($user) {
            $this->command->info('User with ID 1 already exists: ' . $user->name . ' (' . $user->email . ')');
            return;
        }

        $user = User::create([
            'id' => 1,
            'name' => 'iyus yusa',
            'email' => 'yusa@gmail.com',
            'password' => Hash::make('password'), // Default password, change as needed
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('User created: ID=1, Name=iyus yusa, Email=yusa@gmail.com');
    }
}
