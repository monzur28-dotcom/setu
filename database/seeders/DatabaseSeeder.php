<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Order matters. Geo before members (profiles reference district IDs),
 * members before content (stories and advertisements reference profiles).
 *
 * Deliberately absent: any Connect seed data. Connect is opt-in, per
 * person, behind an explicit consent — seeding it would put people in a
 * dating product they never joined, which is exactly the failure the
 * wall exists to prevent. Create one by hand at /connect/start.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            GeoSeeder::class,
            PlanSeeder::class,
            StaffSeeder::class,
            BlockedWordSeeder::class,
            HeroSlideSeeder::class,
            DemoMemberSeeder::class,
            DemoPhotoSeeder::class,
            ContentSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('Seeding complete.');
        $this->command?->line('  Admin:  admin@setu.test  / password');
        $this->command?->line('  Ghotok: ghotok@setu.test / password');
        $this->command?->line('  Member: demo1@setu.test  / password');
    }
}
