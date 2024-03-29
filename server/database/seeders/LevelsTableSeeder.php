<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levelsData = [
            [
                'level_value' => 1,
                'required_xp' => 0,
            ],
            [
                'level_value' => 2,
                'required_xp' => 100,
            ],
            [
                'level_value' => 3,
                'required_xp' => 300,
            ],
            [
                'level_value' => 4,
                'required_xp' => 600,
            ],
            [
                'level_value' => 5,
                'required_xp' => 1000,
            ],
            [
                'level_value' => 6,
                'required_xp' => 1500,
            ],
            [
                'level_value' => 7,
                'required_xp' => 2100,
            ],
            [
                'level_value' => 8,
                'required_xp' => 2800,
            ],
            [
                'level_value' => 9,
                'required_xp' => 3600,
            ],
            [
                'level_value' => 10,
                'required_xp' => 4500,
            ],
        ];

        foreach ($levelsData as $levelData) {
            Level::create($levelData);
        }
    }
}
