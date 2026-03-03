<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // ====== Admin Permissions ======
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage courses']);
        Permission::create(['name' => 'manage roles']);

        // ====== Instructor Permissions ======
        Permission::create(['name' => 'create courses']);
        Permission::create(['name' => 'edit courses']);
        Permission::create(['name' => 'publish courses']);

        // ====== Student Permissions ======
        Permission::create(['name' => 'enroll courses']);
        Permission::create(['name' => 'view courses']);

        // ====== Create Roles ======
        $admin = Role::create(['name' => 'admin']);
        $instructor = Role::create(['name' => 'instructor']);
        $student = Role::create(['name' => 'student']);

        // ====== Assign Permissions to Admin ======
        $admin->givePermissionTo([
            'manage users',
            'manage courses',
            'manage roles',
            'create courses',
            'edit courses',
            'publish courses',
            'enroll courses',
            'view courses',
        ]);

        // ====== Assign Permissions to Instructor ======
        $instructor->givePermissionTo([
            'create courses',
            'edit courses',
            'publish courses',
        ]);

        // ====== Assign Permissions to Student ======
        $student->givePermissionTo([
            'enroll courses',
            'view courses',
        ]);
    }
}
