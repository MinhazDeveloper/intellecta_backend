<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Jhon',
            'email' => 'jhon@intellecta.com',
            'password' => Hash::make('jhon123'),
            'role' => 'admin',
        ]);

        // Instructor
        User::create([
            'name' => 'Emma',
            'email' => 'emma@intellecta.com',
            'password' => Hash::make('emma123'),
            'role' => 'instructor',
        ]);

        // Student
        User::create([
            'name' => 'Kelly',
            'email' => 'kelly@intellecta.com',
            'password' => Hash::make('kelly123'),
            'role' => 'student',
        ]);
    }
}
