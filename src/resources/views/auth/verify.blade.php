@extends('layouts.app')
@section('title','メール認証のお願い')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify.css') }}?v={{ filemtime(public_path('css/verify.css')) }}">
@endsection

@section('content')
<div class="verify-wrap">
  
    <div class="verify-lead">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </div>
    @if (app()->environment('local'))
        <p style="margin:16px 0;">
        <a class="verify-primary" href="http://localhost:8025" target="_blank" rel="noopener"> 認証はこちらから</a>
        </p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="verify-resend">
        @csrf
        <button type="submit" class="verify-resend-link">認証メールを再送する</button>
    </form>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

</div>
@endsection
