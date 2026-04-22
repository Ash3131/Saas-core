<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Permissions
        $view = Permission::firstOrCreate(['name' => 'view_users']);
        $update = Permission::firstOrCreate(['name' => 'update_users']);
        $delete = Permission::firstOrCreate(['name' => 'delete_users']);
        $create = Permission::firstOrCreate(['name' => 'create_users']);

        // Attach permissions to super_admin
        $superAdmin->permissions()->syncWithoutDetaching([
            $view->id,
            $update->id,
            $delete->id,
            $create->id
        ]);

        // Attach permissions to admin
        $admin->permissions()->syncWithoutDetaching([
            $view->id,
            $update->id,
            $create->id
        ]);

        // Attach limited permissions to user
        $user->permissions()->syncWithoutDetaching([
            $view->id
        ]);
    }
}
