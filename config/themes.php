<?php

/*
|--------------------------------------------------------------------------
| Typography
|--------------------------------------------------------------------------
| A curated list, not a free-text field.
|
| Two reasons, both of which have teeth. A font name typed into a form is a
| string this application would put into a stylesheet and a third-party URL,
| which is somewhere untrusted input has no business being. And almost no
| Latin display face carries Bengali glyphs — a site that is half Bangla
| would silently fall back for half its readers, on a screen whose whole
| purpose is choosing how the site looks.
|
| So the Bengali stack is fixed, every pairing here is checked to sit
| alongside it, and the admin picks from this list.
|
| `google` is the Google Fonts query for the pairing. The Bengali faces and
| the mono are appended to every request, because they are always needed.
*/

return [

    'bengali_and_mono' => 'family=IBM+Plex+Mono:wght@400;500'
        .'&family=Hind+Siliguri:wght@400;500;600'
        .'&family=Noto+Serif+Bengali:wght@500;600',

    'pairs' => [
        'newsreader' => [
            'label'   => 'Newsreader & IBM Plex Sans',
            'note'    => 'The original. A quiet editorial serif over a neutral text face.',
            'head'    => 'Newsreader,"Noto Serif Bengali",Georgia,serif',
            'body'    => '"IBM Plex Sans","Hind Siliguri",system-ui,-apple-system,Segoe UI,sans-serif',
            'google'  => 'family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..500'
                        .'&family=IBM+Plex+Sans:wght@300;400;500;600;700',
        ],
        'playfair' => [
            'label'   => 'Playfair Display & Inter',
            'note'    => 'High contrast and formal. Wedding-invitation register.',
            'head'    => '"Playfair Display","Noto Serif Bengali",Georgia,serif',
            'body'    => 'Inter,"Hind Siliguri",system-ui,sans-serif',
            'google'  => 'family=Playfair+Display:wght@400;500;600;700;800'
                        .'&family=Inter:wght@300;400;500;600;700',
        ],
        'lora' => [
            'label'   => 'Lora & Source Sans 3',
            'note'    => 'Warmer and rounder than the default. Reads well at length.',
            'head'    => 'Lora,"Noto Serif Bengali",Georgia,serif',
            'body'    => '"Source Sans 3","Hind Siliguri",system-ui,sans-serif',
            'google'  => 'family=Lora:ital,wght@0,400..700;1,400..700'
                        .'&family=Source+Sans+3:wght@300;400;500;600;700',
        ],
        'fraunces' => [
            'label'   => 'Fraunces & Work Sans',
            'note'    => 'Characterful and modern. The least conservative option here.',
            'head'    => 'Fraunces,"Noto Serif Bengali",Georgia,serif',
            'body'    => '"Work Sans","Hind Siliguri",system-ui,sans-serif',
            'google'  => 'family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700'
                        .'&family=Work+Sans:wght@300;400;500;600;700',
        ],
        'inter' => [
            'label'   => 'Inter throughout',
            'note'    => 'No serif at all. Plainer, more like a product than a publication.',
            'head'    => 'Inter,"Hind Siliguri",system-ui,sans-serif',
            'body'    => 'Inter,"Hind Siliguri",system-ui,sans-serif',
            'google'  => 'family=Inter:wght@300;400;500;600;700;800',
        ],
    ],

    /*
    | Weights offered for headings and body text. Not a free number: 900 on a
    | face that only ships to 700 is synthesised by the browser into
    | something smeared, and 200 body text fails contrast for a lot of people.
    */
    'weights' => [
        'head' => [400 => 'Light', 500 => 'Regular', 600 => 'Medium', 700 => 'Bold'],
        'body' => [300 => 'Light', 400 => 'Regular', 500 => 'Medium', 600 => 'Semibold'],
    ],
];
