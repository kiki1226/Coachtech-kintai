@extends('layouts.admin')
@section('title','管理者ログイン')

{{-- layout側が @yield('css') ならこちらを使う --}}
@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
@endsection

@section('content')
<div class="container">
  <h1 class="title">管理者ログイン</h1>

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul style="margin:0;padding-left:1.2rem;">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.login.post') }}" novalidate>
    @csrf
      <div class="form-group">
        <label>メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}">
        @error('email') <div class="error">{{ $message }}</div> @enderror
      </div>
      <div class="form-group" style="margin-bottom:12px;">
        <label>パスワード</label>
        <input type="password" name="password">
        @error('password') <div class="error">{{ $message }}</div> @enderror
      </div>
      <div class="actions">
        <button type="submit" class="submit">ログイン</button>
      </div>
  </form>
</div>
@endsection
