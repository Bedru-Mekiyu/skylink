<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Admin > Departments (About page team cards). The two cards that were hard-coded on the page are imported exactly as shown. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('tagline', 200)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt', 200)->nullable();
            $table->string('image_label', 80)->nullable();
            $table->string('original_path')->nullable();
            $table->string('accent', 12)->default('primary');
            $table->json('stats')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['visible', 'sort_order']);
        });

        $now = now();
        $rows = [
            ['name' => 'Software Team', 'tagline' => 'Building the future with code', 'icon' => 'fas fa-code', 'image_path' => 'assets/images/contact/team-support.jpg',
                'image_alt' => 'Sky Link team members smiling in front of the Sky Link Technologies logo wall', 'image_label' => 'Innovation Driven', 'accent' => 'primary',
                'stats' => [['n' => '15+', 'l' => 'Developers'], ['n' => '50+', 'l' => 'Projects']]],
            ['name' => 'Marketing Team', 'tagline' => 'Connecting brands with audiences', 'icon' => 'fas fa-bullhorn', 'image_path' => 'assets/images/about/marketing.jpg',
                'image_alt' => 'Social Media & Marketing Team', 'image_label' => 'Creative Excellence', 'accent' => 'info',
                'stats' => [['n' => '10+', 'l' => 'Marketers'], ['n' => '100+', 'l' => 'Campaigns']]],
        ];
        foreach ($rows as $i => $r) {
            DB::table('departments')->insert(array_merge($r, ['stats' => json_encode($r['stats']), 'sort_order' => $i + 1, 'visible' => true, 'created_at' => $now, 'updated_at' => $now]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments'); // uploaded pictures in storage/app/public/departments stay on disk
    }
};
