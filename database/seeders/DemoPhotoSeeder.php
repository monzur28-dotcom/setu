<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A primary photograph for every demo profile, so the browsing surfaces show
 * what the product actually looks like in use instead of a wall of empty
 * monogram tiles.
 *
 * Two variants are written per profile, because the privacy model demands it:
 * `path` is the clear image and `blur_path` is a SEPARATE, genuinely
 * detail-free file. The blur is never a CSS filter over the real image — a
 * viewer can delete a CSS rule, they cannot recover pixels that were never
 * sent. Spec 19.4.
 *
 * Real photographs, if you have them: drop JPEG/PNG files into
 * `database/seeders/demo-photos/` and they are used in filename order,
 * round-robin. With none present the seeder draws an illustrated portrait per
 * profile — deterministic from the profile id, so a reseed is stable. Vector,
 * because this project ships without GD or Imagick.
 */
class DemoPhotoSeeder extends Seeder
{
    /** Backdrop / garment pairs, drawn from the same warm palette as the UI. */
    private const PALETTES = [
        ['#F3E2E4', '#E4BFC5', '#8E1B2E', '#63121F'],
        ['#F6EEDC', '#E6D2A4', '#A87C22', '#7A5714'],
        ['#E7EFEA', '#C2D8CC', '#2F6B57', '#1E4B3C'],
        ['#EFE9F2', '#D3C4DD', '#5B3E77', '#3F2A55'],
        ['#FBEAE1', '#EFC9B2', '#A8582A', '#7A3D1B'],
        ['#E5EBF3', '#C0CEE0', '#2C4A73', '#1C3251'],
    ];

    private const SKIN = ['#E8C39E', '#DDAE84', '#C9946B', '#B87F58'];

    public function run(): void
    {
        $disk    = Storage::disk('photos');
        $sources = $this->sourcePhotos();
        $rows    = [];
        $n       = 0;

        foreach (Profile::all() as $profile) {
            $dir  = 'demo/'.$profile->id;
            $seed = $profile->id;

            if ($sources !== []) {
                $src  = $sources[$n % count($sources)];
                $full = $dir.'/primary.'.strtolower(pathinfo($src, PATHINFO_EXTENSION));
                $disk->put($full, (string) file_get_contents($src));
            } else {
                $full = $dir.'/primary.svg';
                $disk->put($full, $this->portrait($profile->gender, $seed));
            }

            // The hidden variant is built from the palette alone, never from
            // the clear file, so no part of a face survives inside it.
            $blur = $dir.'/primary-blur.svg';
            $disk->put($blur, $this->blurred($seed));

            $rows[] = [
                'profile_id'          => $profile->id,
                'uploaded_by_user_id' => $profile->user_id,
                'path'                => $full,
                'blur_path'           => $blur,
                'order'               => 0,
                'is_primary'          => true,
                'status'              => 'APPROVED',
                'moderated_at'        => now(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ];
            $n++;
        }

        DB::table('photos')->insert($rows);

        $this->command?->info(sprintf(
            '  Photos: %d primary (%s) + %d hidden variants.',
            count($rows),
            $sources === [] ? 'illustrated' : 'from database/seeders/demo-photos',
            count($rows),
        ));
    }

    /** @return list<string> absolute paths of any real photographs supplied. */
    private function sourcePhotos(): array
    {
        $dir = database_path('seeders/demo-photos');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        sort($files);

        return array_values($files);
    }

    /**
     * A head-and-shoulders portrait: backdrop wash, hair, face, garment.
     * Deterministic in $seed, so a profile keeps its picture across reseeds.
     */
    private function portrait(?string $gender, int $seed): string
    {
        [$bgA, $bgB, $garment, $garmentDark] = self::PALETTES[$seed % count(self::PALETTES)];

        $skin = self::SKIN[$seed % count(self::SKIN)];
        $hair = ['#2B1F1C', '#3A2A22', '#1F1715'][$seed % 3];

        // Brides wear a draped anchal over the hair; grooms a plain collar.
        $figure = $gender === 'FEMALE'
            // Long hair, blouse, face, fringe, then the orna draped over the
            // crown and down both sides — drawn last so it sits on top.
            ? '<path d="M112 360C112 260 118 180 148 140c14-20 30-28 52-28s38 8 52 28c30 40 36 120 36 220z" fill="'.$hair.'"/>'
              .'<path d="M154 252c-40 12-64 48-64 94v54h220v-54c0-46-24-82-64-94z" fill="'.$garmentDark.'"/>'
              .'<ellipse cx="200" cy="196" rx="58" ry="68" fill="'.$skin.'"/>'
              .'<path d="M142 196c0-46 26-80 58-80s58 34 58 80c-8-32-28-50-58-50s-50 18-58 50z" fill="'.$hair.'"/>'
              .'<path d="M104 400C104 250 112 168 146 130c16-18 34-26 54-26s38 8 54 26c34 38 42 120 42 270h-38c0-138-6-210-32-242-10-12-20-18-30-18s-20 6-30 18c-26 32-32 104-32 242z" fill="'.$garment.'"/>'
            : '<path d="M156 250c-38 12-60 46-60 90v60h208v-60c0-44-22-78-60-90z" fill="'.$garment.'"/>'
              .'<path d="M156 250l44 42 44-42 16 8-60 56-60-56z" fill="'.$garmentDark.'" opacity=".65"/>'
              .'<ellipse cx="200" cy="196" rx="58" ry="68" fill="'.$skin.'"/>'
              .'<path d="M142 180c0-38 26-66 58-66s58 28 58 66c0 6 0 12-1 18-9-27-29-42-57-42s-48 15-57 42c-1-6-1-12-1-18z" fill="'.$hair.'"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="400" height="400" role="img">'
            .'<defs>'
            .'<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="0" stop-color="'.$bgA.'"/><stop offset="1" stop-color="'.$bgB.'"/>'
            .'</linearGradient>'
            .'<radialGradient id="glow" cx="50%" cy="32%" r="58%">'
            .'<stop offset="0" stop-color="#FFFFFF" stop-opacity=".55"/>'
            .'<stop offset="1" stop-color="#FFFFFF" stop-opacity="0"/>'
            .'</radialGradient>'
            .'</defs>'
            .'<rect width="400" height="400" fill="url(#bg)"/>'
            .'<rect width="400" height="400" fill="url(#glow)"/>'
            .$figure
            .'</svg>';
    }

    /**
     * The hidden variant: three soft blocks of colour under a heavy blur.
     * There is no face in this file to recover.
     */
    private function blurred(int $seed): string
    {
        [$bgA, $bgB, $garment] = self::PALETTES[$seed % count(self::PALETTES)];
        $skin = self::SKIN[$seed % count(self::SKIN)];

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="400" height="400" role="img">'
            .'<defs><filter id="b" x="-25%" y="-25%" width="150%" height="150%">'
            .'<feGaussianBlur stdDeviation="44"/></filter></defs>'
            .'<rect width="400" height="400" fill="'.$bgA.'"/>'
            .'<g filter="url(#b)">'
            .'<rect x="-40" y="-40" width="480" height="260" fill="'.$bgB.'"/>'
            .'<ellipse cx="200" cy="180" rx="86" ry="96" fill="'.$skin.'"/>'
            .'<rect x="50" y="280" width="300" height="200" rx="70" fill="'.$garment.'"/>'
            .'</g>'
            .'</svg>';
    }
}
