@extends('layouts.app')
@section('title', '会員登録')

<link rel="stylesheet" href="{{ asset('css/register.css') }}?v={{ filemtime(public_path('css/register.css')) }}">

@section('content')
<div class="container" style="max-width: 680px;">
    <h1 class="title">会員登録</h1>

    {{-- サマリー（全エラーを上部に表示） --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:1.2rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ブラウザのネイティブ検証を無効化したい時は novalidate を付ける（確認用） --}}
    <form method="POST" action="{{ route('register.post') }}" novalidate>
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                   autocomplete="name" autofocus
                   class="@error('name') is-invalid @enderror">
            @error('name')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   autocomplete="email"
                   class="@error('email') is-invalid @enderror">
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password" required
                   autocomplete="new-password"
                   class="@error('password') is-invalid @enderror">
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">パスワード（確認）</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   autocomplete="new-password"
                   class="@error('password_confirmation') is-invalid @enderror">
            @error('password_confirmation')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="actions">
            <button type="submit" class="btn">登録する</button>
        </div>

        <p class="login">
            <a class="login" href="{{ route('login') }}">ログインはこちら</a>
        </p>
    </form>
</div>
@endsection

