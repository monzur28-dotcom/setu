@extends('layouts.app')
@section('title', __('admin.words'))
@section('noindex', true)
@section('content')
<div class="hd">
    <h1>@lang('admin.words')</h1>
    <span class="sub">@lang('admin.words_sub')</span>
</div>

<div class="grid g2" style="align-items:start">
    <form method="POST" action="{{ route('admin.words.add') }}" class="card pad stack g12">@csrf
        <div class="field">
            <label>@lang('admin.word')</label>
            <input class="inp" name="word" maxlength="60" required>
            <span class="xs muted">@lang('admin.word_help')</span>
        </div>
        <div class="field">
            <label>@lang('admin.word_locale')</label>
            <select class="inp" name="locale">
                <option value="*">@lang('admin.word_any_locale')</option>
                <option value="bn">বাংলা</option>
                <option value="en">English</option>
            </select>
        </div>
        <div class="field">
            <label>@lang('admin.word_note')</label>
            <input class="inp" name="note" maxlength="120">
        </div>
        <button class="btn">@lang('admin.word_add')</button>
    </form>

    <div class="card scrollx">
        <table class="tbl">
            <thead><tr>
                <th>@lang('admin.word')</th><th>@lang('admin.word_locale')</th>
                <th>@lang('admin.word_note')</th><th></th>
            </tr></thead>
            <tbody>
            @forelse ($words as $w)
                <tr>
                    <td class="mono">{{ $w->word }}</td>
                    <td><span class="chip xs">{{ $w->locale === '*' ? __('admin.word_any_locale') : $w->locale }}</span></td>
                    <td class="sm muted">{{ $w->note }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.words.remove', $w) }}">
                            @csrf @method('DELETE')
                            <button class="btn sm ghost">@lang('common.remove')</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">@lang('admin.words_empty')</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
