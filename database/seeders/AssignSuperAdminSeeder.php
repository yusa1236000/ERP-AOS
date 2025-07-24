<?php
// database/seeders/AssignSuperAdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AssignSuperAdminSeeder extends Seeder
{
    public function run()
    {
        $user = User::find(1);

        if (!$user) {
            $this->command->info('User with ID 1 not found.');
            return;
        }

        $this->command->info('User found: ID=' . $user->id . ', Name=' . $user->name . ', Email=' . $user->email);

        $hasRoleBefore = $user->hasRole('super_admin');
        $this->command->info('Has super_admin role before assignment? ' . ($hasRoleBefore ? 'Yes' : 'No'));

        if (!$hasRoleBefore) {
            $role = \App\Models\Role::where('name', 'super_admin')->first();
            if ($role) {
                $user->roles()->attach($role->id, [
                    'assigned_by' => null,
                    'assigned_at' => now(),
                ]);
                $this->command->info('Directly attached super_admin role to user ID 1.');
            } else {
                $this->command->info('Role super_admin not found.');
            }
            $user->load('roles');
            $hasRoleAfter = $user->hasRole('super_admin');
            $this->command->info('Has super_admin role after assignment? ' . ($hasRoleAfter ? 'Yes' : 'No'));
            $this->command->info('Roles after assignment: ' . implode(', ', $user->getRoleNames()));
        } else {
            $this->command->info('User ID 1 already has the super admin role.');
        }
    }
}
