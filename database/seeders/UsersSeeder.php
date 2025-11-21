<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Team;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::truncate();
        Team::truncate();
        User::insert([
            'name' => 'Erwin Erick Ureña Inarra',
            'email' => 'ericksapiens@gmail.com',
            'password' => bcrypt('12345678'),
        ]);        
        Team::insert([
            'user_id' => '1',
            'name' => 'Erick-Team',
            'personal_team' => 'true',
        ]);
        User::insert([
            'name' => 'Evans Balcazar Veizaga',
            'email' => 'evansbv@gmail.com',
            'password' => bcrypt('12345678'),
        ]);
        Team::insert([
            'user_id' => '2',
            'name' => 'Evans-Team',
            'personal_team' => 'true',
        ]);
    }
}
