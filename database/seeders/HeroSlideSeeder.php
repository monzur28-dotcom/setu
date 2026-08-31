<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The photographs behind the front-page headline.
 *
 * Drop real ones into `database/seeders/hero-photos/` — JPEG, PNG or WebP,
 * used in filename order — and they are what ships. With none present the
 * seeder writes four illustrated backdrops so the slideshow is never empty
 * on a fresh install. Either way the admin replaces them at /admin/hero
 * without touching code, which is the point.
 *
 * Vector fallbacks, because this project ships without GD or Imagick. They
 * sit behind a scrim at low contrast, so a wash of colour does the job a
 * photograph would.
 */
class HeroSlideSeeder extends Seeder
{
    /** Caption, product, and the palette its illustrated fallback uses. */
    private const SLIDES = [
        ['The gaye holud, in full colour',     'MATRIMONIAL', ['#A81C2E', '#E0642F', '#F6C453'], 'festival'],
        ['A quiet moment after the ceremony', 'BOTH',        ['#8E1B2E', '#D98A5B', '#F3D9B1'], 'dusk'],
        ['Confetti on the steps',              'BOTH',        ['#5B3E77', '#B2789B', '#F0D5D8'], 'petals'],
        ['Somewhere new, together',            'BOTH',        ['#1B5249', '#3E9C93', '#BFE3DA'], 'shore'],
    ];

    public function run(): void
    {
        $disk    = Storage::disk('hero');
        $sources = $this->sourcePhotos();
        $rows    = [];

        foreach (self::SLIDES as $i => [$caption, $product, $palette, $scene]) {
            if (isset($sources[$i])) {
                $path = 'slide-'.($i + 1).'.'.strtolower(pathinfo($sources[$i], PATHINFO_EXTENSION));
                $disk->put($path, (string) file_get_contents($sources[$i]));
            } else {
                $path = 'slide-'.($i + 1).'.svg';
                $disk->put($path, $this->backdrop($palette, $scene));
            }

            $rows[] = [
                'path' => $path, 'caption' => $caption, 'product' => $product,
                'sort_order' => $i, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }

        DB::table('hero_slides')->insert($rows);

        $this->command?->info(sprintf(
            '  Hero: %d slides (%s). Replace them at /admin/hero.',
            count($rows),
            $sources === [] ? 'illustrated' : 'from database/seeders/hero-photos',
        ));
    }

    /** @return list<string> */
    private function sourcePhotos(): array
    {
        $dir = database_path('seeders/hero-photos');

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir.'/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        sort($files);

        return array_values($files);
    }

    /** A 1600x900 wash with a horizon and a couple of soft silhouettes. */
    private function backdrop(array $palette, string $scene): string
    {
        [$deep, $mid, $light] = $palette;

        $sceneArt = match ($scene) {
            // Two figures on a bench against a low sun.
            'dusk' => '<circle cx="1120" cy="470" r="120" fill="#FFF3D6" opacity=".55"/>'
                .'<rect x="0" y="620" width="1600" height="280" fill="'.$deep.'" opacity=".45"/>'
                .'<ellipse cx="740" cy="600" rx="52" ry="78" fill="'.$deep.'" opacity=".7"/>'
                .'<ellipse cx="848" cy="590" rx="56" ry="88" fill="'.$deep.'" opacity=".7"/>'
                .'<rect x="640" y="640" width="330" height="16" rx="6" fill="'.$deep.'" opacity=".8"/>',
            // Scattered marigold, the gaye holud.
            'festival' => implode('', array_map(
                fn ($i) => '<circle cx="'.(90 + $i * 137).'" cy="'.(180 + (($i * 97) % 520)).'" r="'.(14 + ($i % 4) * 7).'" fill="'.$light.'" opacity=".5"/>',
                range(0, 10),
            )).'<rect x="0" y="700" width="1600" height="200" fill="'.$deep.'" opacity=".35"/>',
            // Confetti falling past columns.
            'petals' => implode('', array_map(
                fn ($i) => '<ellipse cx="'.(70 + $i * 105).'" cy="'.(120 + (($i * 151) % 640)).'" rx="16" ry="9" fill="'.$light.'" opacity=".55" transform="rotate('.(($i * 37) % 180).' '.(70 + $i * 105).' '.(120 + (($i * 151) % 640)).')"/>',
                range(0, 14),
            )),
            // A shoreline.
            default => '<rect x="0" y="540" width="1600" height="360" fill="'.$mid.'" opacity=".55"/>'
                .'<rect x="0" y="760" width="1600" height="140" fill="'.$light.'" opacity=".5"/>'
                .'<ellipse cx="700" cy="600" rx="34" ry="96" fill="'.$deep.'" opacity=".6"/>'
                .'<ellipse cx="800" cy="604" rx="32" ry="92" fill="'.$deep.'" opacity=".6"/>',
        };

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 900" width="1600" height="900" role="img">'
            .'<defs><linearGradient id="g" x1="0" y1="0" x2="0.3" y2="1">'
            .'<stop offset="0" stop-color="'.$light.'"/>'
            .'<stop offset="0.55" stop-color="'.$mid.'"/>'
            .'<stop offset="1" stop-color="'.$deep.'"/>'
            .'</linearGradient></defs>'
            .'<rect width="1600" height="900" fill="url(#g)"/>'
            .$sceneArt
            .'</svg>';
    }
}
