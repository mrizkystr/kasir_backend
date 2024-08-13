<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat akun admin
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'role' => 'admin'
        ]);

        // Buat akun karyawan
        $karyawan = User::create([
            'username' => 'karyawan',
            'email' => 'karyawan@gmail.com',
            'password' => bcrypt('karyawan'),
            'role' => 'karyawan'
        ]);

        // Assign role dan permissions untuk admin
        $adminRole = Role::findByName('admin');
        $adminPermissions = Permission::all();
        $adminRole->syncPermissions($adminPermissions);
        $admin->assignRole($adminRole);

        // Assign role untuk karyawan
        $karyawanRole = Role::findByName('karyawan');
        $karyawan->assignRole($karyawanRole);
    }
}

