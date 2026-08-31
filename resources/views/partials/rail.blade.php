@php
    $connect = ($mode ?? 'matrimonial') === 'connect';
    $u = auth()->user();
@endphp
<aside class="rail" id="rail">
    <div class="rail-head">
        <a class="mark" href="{{ $connect ? route('connect.deck') : route('home') }}">
            <span class="glyph">{{ $connect ? 'প' : 'সে' }}</span>
            <span>
                <span class="wordmark">{{ config('app.name') }}</span><br>
                <span class="modetag">{{ $connect ? __('nav.connect') : __('nav.matrimonial') }}</span>
            </span>
        </a>
    </div>

    <nav>
        @if ($connect)
            <div class="navgroup">
                <span class="lbl">@lang('nav.connect')</span>
                <a class="navitem {{ request()->routeIs('connect.deck') ? 'on' : '' }}" href="{{ route('connect.deck') }}"><span class="ic">▣</span>@lang('nav.suggestions')</a>
                <a class="navitem {{ request()->routeIs('connect.matches') ? 'on' : '' }}" href="{{ route('connect.matches') }}"><span class="ic">∞</span>@lang('nav.matches')</a>
                <a class="navitem {{ request()->routeIs('connect.profile.*') ? 'on' : '' }}" href="{{ route('connect.profile.edit') }}"><span class="ic">◇</span>@lang('nav.my_profile')</a>
                <a class="navitem {{ request()->routeIs('connect.plans') ? 'on' : '' }}" href="{{ route('connect.plans') }}"><span class="ic">৳</span>@lang('nav.plans')</a>
                <a class="navitem {{ request()->routeIs('connect.settings') ? 'on' : '' }}" href="{{ route('connect.settings') }}"><span class="ic">⚙</span>@lang('nav.settings')</a>
            </div>
            <div class="navgroup" style="border-top:1px solid var(--line); padding-top:12px">
                <a class="navitem" href="{{ route('member.dashboard') }}"><span class="ic">⌂</span>@lang('nav.back_to_marriage')</a>
            </div>
        @else
            <div class="navgroup">
                <span class="lbl">@lang('nav.browse')</span>
                <a class="navitem {{ request()->routeIs('home') ? 'on' : '' }}" href="{{ route('home') }}"><span class="ic">◇</span>@lang('nav.home')</a>
                <a class="navitem {{ request()->routeIs('public.search') || request()->routeIs('member.search') ? 'on' : '' }}"
                   href="{{ auth()->check() ? route('member.search') : route('public.search') }}"><span class="ic">⌕</span>@lang('nav.search')</a>
                <a class="navitem {{ request()->routeIs('plans') ? 'on' : '' }}" href="{{ route('plans') }}"><span class="ic">৳</span>@lang('nav.plans')</a>
                <a class="navitem {{ request()->routeIs('safety') ? 'on' : '' }}" href="{{ route('safety') }}"><span class="ic">⚑</span>@lang('nav.safety')</a>
            </div>

            <div class="navgroup">
                <span class="lbl">@lang('nav.free_tools')</span>
                <a class="navitem {{ request()->routeIs('biodata.*') ? 'on' : '' }}" href="{{ route('biodata.create') }}"><span class="ic">✎</span>@lang('nav.biodata')</a>
                <a class="navitem {{ request()->routeIs('classifieds.*') ? 'on' : '' }}" href="{{ route('classifieds.index') }}"><span class="ic">☰</span>@lang('nav.classifieds')</a>
            </div>

            @auth
                <div class="navgroup">
                    <span class="lbl">@lang('nav.member')</span>
                    <a class="navitem {{ request()->routeIs('member.dashboard') ? 'on' : '' }}" href="{{ route('member.dashboard') }}"><span class="ic">⌂</span>@lang('nav.dashboard')</a>
                    <a class="navitem {{ request()->routeIs('member.privacy') ? 'on' : '' }}" href="{{ route('member.privacy') }}"><span class="ic">◐</span>@lang('nav.privacy')</a>
                    <a class="navitem {{ request()->routeIs('member.interests') ? 'on' : '' }}" href="{{ route('member.interests') }}"><span class="ic">♡</span>@lang('nav.interests')</a>
                    <a class="navitem {{ request()->routeIs('member.access') ? 'on' : '' }}" href="{{ route('member.access') }}"><span class="ic">⚿</span>@lang('nav.access')</a>
                    <a class="navitem {{ request()->routeIs('member.mailbox*') ? 'on' : '' }}" href="{{ route('member.mailbox') }}"><span class="ic">✉</span>@lang('nav.messages')</a>
                    <a class="navitem {{ request()->routeIs('member.family') ? 'on' : '' }}" href="{{ route('member.family') }}"><span class="ic">⚭</span>@lang('nav.family')</a>
                    <a class="navitem {{ request()->routeIs('member.verification') ? 'on' : '' }}" href="{{ route('member.verification') }}"><span class="ic">◆</span>@lang('nav.verification')</a>
                    <a class="navitem {{ request()->routeIs('member.settings') ? 'on' : '' }}" href="{{ route('member.settings') }}"><span class="ic">⚙</span>@lang('nav.settings')</a>
                </div>

                @if ($u?->role === 'OPERATOR' || $u?->role === 'ADMIN')
                    <div class="navgroup">
                        <span class="lbl">@lang('nav.staff')</span>
                        <a class="navitem" href="{{ route('operator.cases') }}"><span class="ic">▦</span>@lang('nav.cases')</a>
                        @if ($u->isStaff())
                            <a class="navitem" href="{{ route('admin.dashboard') }}"><span class="ic">⚙</span>@lang('nav.operations')</a>
                        @endif
                    </div>
                @endif

                {{-- The only entry point to Connect. Never a tab, never in the
                     public navigation, never shown to a guardian or operator. --}}
                @if (! in_array($u?->role, ['OPERATOR', 'GUARDIAN'], true))
                    <div class="navgroup" style="border-top:1px solid var(--line); padding-top:12px">
                        <a class="navitem" href="{{ route('connect.start') }}"><span class="ic">◈</span>@lang('nav.open_connect')</a>
                        <div class="xs muted" style="padding:4px 9px 0; line-height:1.4">@lang('nav.connect_note')</div>
                    </div>
                @endif
            @endauth
        @endif
    </nav>

    <div class="rail-foot">
        <div class="row between g8">
            <a class="btn quiet sm" href="{{ request()->fullUrlWithQuery(['lang' => app()->getLocale() === 'bn' ? 'en' : 'bn']) }}">
                {{ app()->getLocale() === 'bn' ? 'English' : 'বাংলা' }}
            </a>
            @auth
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn quiet sm">@lang('nav.logout')</button>
                </form>
            @else
                <a class="btn quiet sm" href="{{ route('login') }}">@lang('nav.login')</a>
            @endauth
        </div>
    </div>
</aside>
