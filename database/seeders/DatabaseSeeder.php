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
    /**
     * Demo members are for development. In production they are opt-in, so
     * that forgetting the flag gives you an empty site rather than a public
     * one full of people who do not exist.
     */
    private function wantsDemoData(): bool
    {
        $explicit = env('SETU_SEED_DEMO');

        if ($explicit !== null) {
            return filter_var($explicit, FILTER_VALIDATE_BOOLEAN);
        }

        return ! app()->environment('production');
    }

    public function run(): void
    {
        /*
         | Reference data. Always safe: geography, pricing, the staff logins,
         | the word list, the front-page slides. A real deployment needs all
         | of it and none of it invents a person.
         */
        $this->call([
            GeoSeeder::class,
            PlanSeeder::class,
            StaffSeeder::class,
            BlockedWordSeeder::class,
            HeroSlideSeeder::class,
        ]);

        /*
         | Forty invented members with invented photographs. Wanted on a
         | laptop, emphatically not wanted in the real database — a live
         | matrimonial site whose search results are fictional people is
         | worse than one with an empty search.
         |
         | Off in production unless SETU_SEED_DEMO is explicitly set, so the
         | dangerous direction needs a deliberate act and the safe direction
         | is the default.
         */
        if ($this->wantsDemoData()) {
            $this->call([
                DemoMemberSeeder::class,
                DemoPhotoSeeder::class,
            ]);
        } else {
            $this->command?->warn('  Demo members skipped (production). Set SETU_SEED_DEMO=true to include them.');
        }

        // Landing pages and guides: real content, no invented people.
        $this->call([ContentSeeder::class]);

        $password = SeedPassword::get();

        $this->command?->newLine();
        $this->command?->info('Seeding complete.');
        $this->command?->line('  Admin:  admin@setu.test');
        $this->command?->line('  Ghotok: ghotok@setu.test');

        if ($this->wantsDemoData()) {
            $this->command?->line('  Member: demo1@setu.test');
        }
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
