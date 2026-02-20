<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'مينا',
                'email' => 'mena@gmail.com',
                'password' => '@Commander123',
            ],
            [
                'name' => 'أحمد',
                'email' => 'ahmed@ahmed.com',
                'password' => 'ahmed123456',
            ]
        ];
        foreach ($users as $user) {
            User::create($user);
        }
    }
}
