<?php
// database/seeders/ForceAssignSuperAdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class ForceAssignSuperAdminSeeder extends Seeder
{
    public function run()
    {
        $user = User::find(1);

        if (!$user) {
            $this->command->info('User with ID 1 not found.');
            return;
        }

        $this->command->info('User found: ID=' . $user->id . ', Name=' . $user->name . ', Email=' . $user->email);

        $role = Role::where('name', 'super_admin')->first();

        if (!$role) {
            $this->command->info('Role super_admin not found.');
            return;
        }

        // Detach all roles first to avoid duplicates or conflicts
        $user->roles()->detach();

        // Attach super_admin role forcibly
        $user->roles()->attach($role->id, [
            'assigned_by' => null,
            'assigned_at' => now(),
        ]);

        $user->load('roles');
        $this->command->info('Roles after assignment: ' . implode(', ', $user->getRoleNames()));
    }
}
