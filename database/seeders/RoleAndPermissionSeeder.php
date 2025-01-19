<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate the permissions and roles tables
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        
        Permission::truncate();
        Role::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');


        $permissions = [
            'dashboard',
            'manage.applications',
            'manage.resumes',
            'manage.templates',
            'manage.profile',
            'manage.users',
            'manage.notifications',
            'manage.settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => Role::ADMIN]);
        $clientRole = Role::firstOrCreate(['name' => Role::CLIENT]);

        $adminRole->givePermissionTo($permissions);

        $clientPermissions = collect($permissions)->intersect([
            'dashboard',
            'manage.applications',
            'manage.resumes',
            'manage.templates',
            'manage.profile'
        ])->all();

        $clientRole->givePermissionTo($clientPermissions);
    }
}
