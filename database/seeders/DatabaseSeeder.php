<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call([
        //     ProductSeeder::class,
        // ]);

        User::create([
            'name' => 'Bernardo',
            'email' => 'bernardo@olgacolor.com',
            'password' => Hash::make('olga1234'),
        ]);
    }
}
