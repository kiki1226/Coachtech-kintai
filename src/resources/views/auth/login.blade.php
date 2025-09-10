@extends('layouts.app')
@section('title','ログイン')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
@endsection

@section('content')
<div class="container" style="max-width: 680px;">
    <h1 class="title">ログイン</h1>

    {{-- 上部サマリー --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin:0;padding-left:1.2rem;">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" novalidate>
        @csrf

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   autocomplete="email" autofocus class="@error('email') is-invalid @enderror">
            @error('email') 
              <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input id="password" type="password" name="password" required
                   autocomplete="current-password" class="@error('password') is-invalid @enderror">
            @error('password')
             <div class="error">{{ $message }}</div> 
            @enderror
        </div>

        <div class="actions">
            <button type="submit" class="submit">ログイン</button>
        </div>

        <p class="login">
            <a class="login" href="{{ route('register') }}">新規登録はこちら</a>
        </p>
    </form>
</div>
@endsection
