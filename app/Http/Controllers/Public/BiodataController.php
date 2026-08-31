<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BiodataDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The free biodata maker — the highest-return page in the build relative to
 * its cost. It intercepts families at the moment they decide to start
 * looking, before they have chosen a platform, collects exactly the data a
 * profile needs, and sends them away holding a document with your brand on it.
 *
 * NO SIGNUP. Not for the form, not for the preview, not for the download.
 * Asking for an email before delivering the value destroys the point.
 * Spec 9.1.
 */
class BiodataController extends Controller
{
    public function create()
    {
        return view('public.biodata');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'       => ['required', 'string', 'max:80'],
            'date_of_birth'   => ['nullable', 'string', 'max:20'],
            'height'          => ['nullable', 'string', 'max:20'],
            'religion'        => ['nullable', 'string', 'max:40'],
            'marital_status'  => ['nullable', 'string', 'max:40'],
            'city'            => ['nullable', 'string', 'max:60'],
            'home_district'   => ['nullable', 'string', 'max:60'],
            'education'       => ['nullable', 'string', 'max:120'],
            'profession'      => ['nullable', 'string', 'max:80'],
            'father'          => ['nullable', 'string', 'max:120'],
            'mother'          => ['nullable', 'string', 'max:120'],
            'siblings'        => ['nullable', 'string', 'max:120'],
            'expectations'    => ['nullable', 'string', 'max:600'],
            'contact'         => ['nullable', 'string', 'max:60'],
            'template'        => ['nullable', 'in:traditional,modern,formal,compact'],
        ]);

        $draft = BiodataDraft::create([
            'token'    => Str::random(48),
            'payload'  => $data,
            'locale'   => app()->getLocale(),
            'template' => $data['template'] ?? 'traditional',
        ]);

        return redirect()->route('biodata.preview', $draft->token);
    }

    public function preview(string $token)
    {
        $draft = BiodataDraft::where('token', $token)->firstOrFail();

        return view('public.biodata-preview', compact('draft'));
    }

    /**
     * The invitation appears AFTER the download completes, never before —
     * one honest, dismissible line. The signed token pre-fills registration,
     * which is the entire conversion mechanic. Spec 9.1.
     */
    public function convert(string $token)
    {
        $draft = BiodataDraft::where('token', $token)->firstOrFail();

        session(['biodata_token' => $draft->token]);

        return redirect()->route('register');
    }
}
