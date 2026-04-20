<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create Doctor account
        User::create([
            'name'     => 'Dr. Mohamed',
            'email'    => 'doctor@clinic.com',
            'password' => Hash::make('doctor123'),
            'phone'    => '0555000001',
            'role'     => 'doctor',
        ]);

        // Create Assistant account
        User::create([
            'name'     => 'Assistant Sara',
            'email'    => 'assistant@clinic.com',
            'password' => Hash::make('assistant123'),
            'phone'    => '0555000002',
            'role'     => 'assistant',
        ]);
    }
}