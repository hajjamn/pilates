<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'cliente' => [
                'view calendar',
                'book my lesson',
                'cancel my booking',
                'view my packages',
                'view my lessons',
                'view my digital lessons',
                'view operator profile',
                'view room',
            ],
            'operatore' => [
                'view calendar',
                'edit own availability',
                'manage own lessons',
                'view room',
            ],
            'admin' => [
                'manage users',
                'manage packages',
                'manage lessons',
                'manage machines',
                'manage rooms',
                'manage digital lessons',
                'view calendar',
                'view operator profile',
                'view room',
            ],
        ];

        foreach ($permissions as $roleName => $rolePermissions) {
            $role = Role::findByName($roleName);
            foreach ($rolePermissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $role->givePermissionTo($permission);
            }
        }
    }
}
