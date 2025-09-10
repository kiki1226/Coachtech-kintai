{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', '勤怠管理アプリ')</title>

  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
  @yield('css')
</head>

<body>
  <header class="site-header">
    <div class="header-inner">
      {{-- ロゴ（未ログインは /、ログイン済は権限別のトップ） --}}
      <a class="brand" href="{{ auth()->check()
          ? (auth()->user()->can('manage') ? route('admin.attendances.index') : route('attendance.register'))
          : url('/') }}">
        <h1 class="header-title">
          <img src="{{ asset('products/logo.svg') }}" alt="COACHTECH">
        </h1>
      </a>

      {{-- ナビゲーション --}}
      @auth
        @php
          $user = auth()->user();
          // 今いるルートがメール認証系かどうかだけで判定
          $onVerifyRoute = request()->routeIs('verification.*');
        @endphp

        <nav class="header-nav">
          @if($onVerifyRoute && $user && method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail())
            {{-- 誘導ページのときだけ表示 --}}
            <a href="{{ route('verification.notice') }}">メール認証へ</a>
          @else
            {{-- 通常メニュー（verified配下では常にこちら） --}}
            <a href="{{ route('attendance.register') }}">勤怠</a>

            @can('manage')
              <a href="{{ route('admin.attendances.index') }}">勤怠一覧</a>
              <a href="{{ route('admin.users.index') }}">スタッフ一覧</a>
              <a href="{{ route('admin.requests.index') }}">申請</a>
            @else
              <a href="{{ route('attendance.index') }}">勤怠一覧</a>
              <a href="{{ route('requests.index') }}">申請</a>
            @endcan
          @endif

          <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="linklike">ログアウト</button>
          </form>
        </nav>
      @endauth
    </div>
  </header>

  <main class="wrap">
    <div class="card">
      @yield('content')
      @if (session('success'))
      <div class="alert alert-success" style="margin:12px 0; padding:10px; background:#e6ffed; border:1px solid #b7f5c4;">
        {{ session('success') }}
      </div>
      @endif
    </div>
  </main>

  @yield('script')
</body>
</html>
