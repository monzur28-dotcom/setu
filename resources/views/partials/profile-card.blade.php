@php $photo = $p['photos'][0] ?? null; @endphp
<article class="pcard">
    <div style="padding:12px 12px 0">
        @include('partials.photo', [
            'name' => $p['display_name'] ?? '?',
            'blurred' => $photo['blurred'] ?? true,
            'url' => $photo['url'] ?? null,
            'size' => 120,
        ])
    </div>
    <div class="body">
        <div class="row between center g8">
            <span class="nm">{{ $p['display_name'] ?? '—' }}</span>
            @isset($p['score'])
                <span class="mono xs muted">{{ $p['score'] }}%</span>
            @endisset
        </div>
        <div class="meta">
            {{ $p['age'] ?? '' }}
            @isset($p['district']) · {{ $p['district'] }} @endisset<br>
            {{ $p['profession'] ?? $p['education_level'] ?? '' }}
        </div>
        <div class="row wrap g4" style="margin-top:2px">
            @if (in_array($p['verified'] ?? '', ['NID', 'NID_SELFIE']))
                <span class="chip ok">◆ @lang('profile.nid_verified')</span>
            @else
                <span class="chip">◆ @lang('profile.phone_verified')</span>
            @endif
            @isset($p['last_active'])<span class="chip xs">{{ $p['last_active'] }}</span>@endisset
        </div>
    </div>
    <div class="acts">
        <a class="btn sm ghost grow" href="{{ route('public.profile', $p['profile_id']) }}">@lang('common.view')</a>
        @auth
            <form method="POST" action="{{ route('member.interest.send', $p['profile_id']) }}" class="grow">@csrf
                <button class="btn sm full">@lang('interest.express')</button>
            </form>
        @endauth
    </div>
</article>
