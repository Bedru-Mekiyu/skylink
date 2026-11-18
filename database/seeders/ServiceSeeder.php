<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $servicesData = json_decode(file_get_contents(database_path('seeders/services.json')), true);

        foreach ($servicesData as $categorySlug => $category) {
            // The category may already exist (the service-category migration creates the
            // eight /services cards): reuse it instead of inserting a duplicate slug.
            $categoryId = DB::table('service_categories')->where('slug', $categorySlug)->value('id')
                ?? DB::table('service_categories')->insertGetId([
                    'slug' => $categorySlug,
                    'name' => $category['category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($category['items'] as $item) {
                DB::table('services')->insert([
                    'category_id' => $categoryId,
                    'slug' => $item['slug'],
                    'title' => $item['title'],
                    'desc' => $item['desc'],
                    'image' => $item['image'],
                    'price' => $item['price'],
                    'features' => json_encode($item['features']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
