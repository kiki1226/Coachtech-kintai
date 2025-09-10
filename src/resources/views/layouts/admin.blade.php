<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','管理者：勤怠一覧画面')</title>
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
  @yield('css')
</head>

<body>
  <header>
    <div class="bar">
      @php
        // 管理者としてログイン済みか判定（guardはwebでis_adminフラグ運用）
        $isAdmin = auth()->check() && (auth()->user()->is_admin ?? false);
      @endphp

      {{-- ブランドロゴ：未ログイン時は /admin/login に、ログイン時はダッシュボードに --}}
      <a class="brand" href="{{ $isAdmin ? route('admin.attendances.index') : route('admin.login.form') }}">
        <img src="{{ asset('products/logo.svg') }}" alt="COACHTECH">
      </a>

      {{-- ★ 管理者ログイン時のみナビを表示 --}}
      @if($isAdmin)
        <nav class="nav">
          <a href="{{ route('admin.attendances.index') }}">勤怠一覧</a>
          <a href="{{ route('admin.users.index') }}">スタッフ一覧</a>
          <a href="{{ route('admin.requests.index') }}">申請一覧</a>
          <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">ログアウト</a>
        </nav>
        <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" style="display:none;">
          @csrf
        </form>
      @endif
    </div>          
  </header>
  <main class="main">
    <div class="wrap">
      <div class="card">
        @yield('content')
        @if (session('success'))
          <div class="alert alert-success" style="margin:12px 0; padding:10px; background:#e6ffed; border:1px solid #b7f5c4;">
            {{ session('success') }}
          </div>
        @endif
      </div>
    </div>
  </main>
  @yield('script')
</body>
</html>
