<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformSettingSeeder::class,
            UploadLimitSeeder::class,
            UserSeeder::class,
            PostSeeder::class,
            ArticleSeeder::class,
            InteractionSeeder::class,
        ]);
    }
}