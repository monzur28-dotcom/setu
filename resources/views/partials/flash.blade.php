@if (session('status'))
    <div class="note brand" style="margin-bottom:18px">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="note bad" style="margin-bottom:18px">
        <span class="t">@lang('common.please_fix')</span>
        <ul style="margin:6px 0 0 16px; padding:0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
