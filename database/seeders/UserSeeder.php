<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'james@intellecta.com'], // সার্চ করার জন্য
            [
                'name' => 'James',
                'password' => Hash::make('james123'),
                'role' => 'admin',
            ]
        );

        // Instructor
        User::updateOrCreate(
            ['email' => 'emma@intellecta.com'], // সার্চ করার জন্য
            [
                'name' => 'Emma',
                'password' => Hash::make('emma123'),
                'role' => 'instructor',
            ]
        );

        // Student
        User::updateOrCreate(
            ['email' => 'kelly@intellecta.com'], // সার্চ করার জন্য
            [
                'name' => 'Kelly',
                'password' => Hash::make('kelly123'),
                'role' => 'student',
            ]
        );
    }
}
