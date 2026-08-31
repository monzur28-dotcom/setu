@extends('layouts.app')
@section('title', __('nav.verification'))
@section('noindex', true)
@section('crumb', 'Member · Verification')

@section('content')
<div class="hd">
    <h1>@lang('verify.title')</h1>
    <p class="sub">@lang('verify.sub')</p>
</div>

<div class="stack g12" style="max-width:640px">
    @foreach ([
        ['PHONE', true], ['EMAIL', (bool) auth()->user()->email_verified_at],
        ['NID', $verifications->where('type','NID')->where('status','APPROVED')->isNotEmpty()],
        ['SELFIE', $verifications->where('type','SELFIE')->where('status','APPROVED')->isNotEmpty()],
    ] as [$type, $done])
        <div class="card pad row between center wrap g12">
            <div class="stack g4" style="min-width:220px">
                <div class="row g8 center">
                    <span class="b sm">@lang('verify.type_'.strtolower($type))</span>
                    @if ($done)<span class="chip ok">✓ @lang('common.done')</span>@endif
                </div>
                <div class="xs muted">@lang('verify.desc_'.strtolower($type))</div>
            </div>
            @unless ($done)
                @if (in_array($type, ['NID', 'SELFIE']))
                    <form method="POST" action="{{ route('member.verification.store') }}" enctype="multipart/form-data" class="row g6">@csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input class="inp sm" type="file" name="document" required style="width:170px">
                        <button class="btn sm ghost">@lang('common.upload')</button>
                    </form>
                @endif
            @endunless
        </div>
    @endforeach
</div>

<div class="note sec" style="max-width:640px">
    <span class="t">@lang('verify.document_title')</span>
    @lang('verify.document_body')
</div>
@endsection
