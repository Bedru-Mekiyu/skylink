<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > Partners: the logos of the homepage "Trusted Partners" marquee.
 * Creates the table and imports the 27 logos that were hard-coded in the homepage, in the same order, so the
 * homepage looks the same. Imported rows point at the existing static files (legacy_src); run
 * `php artisan partners:optimize-legacy` once to give them small web copies (addis.png is 1 MB).
 * Names come from the old file names: the owner can fix them in the admin. Additive.
 *
 * Rollback: php artisan migrate:rollback --path=database/migrations/2026_11_23_100000_create_partners_table.php
 * (the homepage then falls back to the static logo list).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('alt', 160)->nullable();
            $table->string('website_url', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('since_year')->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('logo_path', 255)->nullable();      // web copy (WebP) on the public disk
            $table->string('logo_fallback', 255)->nullable();  // PNG/JPEG twin of the WebP
            $table->string('legacy_src', 255)->nullable();     // static file under public/ (imported rows)
            $table->unsignedSmallInteger('logo_w')->nullable();
            $table->unsignedSmallInteger('logo_h')->nullable();
            $table->string('original_path', 255)->nullable();  // private archive copy of the upload
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['active', 'featured', 'sort_order']);
        });

        $now = now();
        $order = 0;
        foreach ([
            ['assets/images/minbertv.webp', 'Minber'],
            ['assets/images/iman.webp', 'Iman'],
            ['assets/images/Ethiotel.jpg', 'Ethiotel'],
            ['assets/images/Safaricom.png', 'Safaricom'],
            ['assets/images/trusted/ad.png', 'Ad'],
            ['assets/images/trusted/amar.jpg', 'Amar'],
            ['assets/images/trusted/amin.png', 'Amin'],
            ['assets/images/trusted/basira.png', 'Basira'],
            ['assets/images/trusted/bilalul.png', 'Bilalul'],
            ['assets/images/trusted/kasma.png', 'Kasma'],
            ['assets/images/trusted/mslegue.JPG', 'Msleguer'],
            ['assets/images/newleaf.webp', 'Newleaf'],
            ['assets/images/trusted/nujum.jpg', 'Nujum'],
            ['assets/images/trusted/popular.png', 'Poplar'],
            ['assets/images/trusted/ethioceramics.jpg', 'Ethioceramics'],
            ['assets/images/trusted/softvalley.jpg', 'Softvalley'],
            ['assets/images/befan.jpg', 'Befam'],
            ['assets/images/trusted/ukaz.jpg', 'Ukaz'],
            ['assets/images/trusted/sms.png', 'SMS'],
            ['assets/images/trusted/unknown.PNG', 'Unknown'],
            ['assets/images/evergreen.jfif', 'Evergreen'],
            ['assets/images/aauLogo.png', 'AAU'],
            ['assets/images/trusted/diredawa.png', 'Diredawa'],
            ['assets/images/trusted/addis.png', 'Addis'],
            ['assets/images/trusted/alfa.jpeg', 'Alfa'],
            ['assets/images/trusted/hilco.png', 'Hilco'],
            ['assets/images/trusted/maroli.JPG', 'Maroli'],
        ] as [$src, $name]) {
            $file = public_path($src);
            $size = is_file($file) ? @getimagesize($file) : false;
            DB::table('partners')->insert([
                'name' => $name, 'alt' => $name, 'legacy_src' => $src,
                'logo_w' => $size[0] ?? null, 'logo_h' => $size[1] ?? null,
                'sort_order' => ++$order, 'active' => true, 'featured' => false,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
