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

        $password = SeedPassword::get();

        $this->command?->newLine();
        $this->command?->info('Seeding complete.');
        $this->command?->line('  Admin:  admin@setu.test');
        $this->command?->line('  Ghotok: ghotok@setu.test');
        $this->command?->line('  Member: demo1@setu.test');
        $this->command?->line('  Password: '.$password);

        if (SeedPassword::isGenerated()) {
            // Printed once and nowhere else. There is no way to read it back
            // out of the database, which is the point of hashing it.
            $this->command?->newLine();
            $this->command?->warn('  ^ Generated for this environment and shown only here.');
            $this->command?->warn('    Copy it now, or set SETU_SEED_PASSWORD and reseed.');
        }
    }
}
