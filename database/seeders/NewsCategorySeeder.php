<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NewsCategory;


class NewsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        $categories = [
            'News',
            'Events',
            'Innovations',
        ];

        foreach ($categories as $name) {
            NewsCategory::firstOrCreate(['name' => $name]);
        }
    }
}
