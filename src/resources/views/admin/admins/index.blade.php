@extends('layouts.admin')
@section('title','管理者ユーザー')
  <link rel="stylesheet"
      href="{{ asset('css/admin.admins.index.css') }}?v={{ filemtime(public_path('css/admin.admins.index.css')) }}">
@section('content')
<div class="alert-success">
  <div class="alert-space">　</div>
  <h1 class="page-title">管理者ユーザー管理</h1>
</div>
<div class="mb-3">
  <a href="{{ route('admin.users.index') }}" class="btn btn-outline">← スタッフ一覧に戻る</a>
</div>
{{-- 新規作成 --}}
<h2 class="titol-twe">新規の管理者を作成</h2>
<form method="post" action="{{ route('admin.admins.store') }}" class="form">
  @csrf
  <div class="sinki">
    <input name="name" placeholder="名前" value="{{ old('name') }}" class="input">
    <input name="email" placeholder="メール" value="{{ old('email') }}" class="input">
    <input name="password" type="password" placeholder="パスワード" class="input">
  </div>
  <button class="botton">作成</button>
</form>

{{-- 既存昇格 --}}
<h2 class="titol-twe">既存ユーザーを昇格</h2>
<div class="kizon">
  <form method="post" action="{{ route('admin.admins.promote') }}" class="form">
    @csrf
    <input name="email" placeholder="ユーザーのメール" class="input-1">
    <button class="botton">管理者に昇格</button>
  </form>
  
</div>

{{-- 一覧 --}}
<h2 class="titol-twe">管理者一覧</h2>
<table>
  <tr><th>ID</th><th>名前</th><th>メール</th><th>操作</th></tr>
  @foreach($admins as $a)
  <tr>
    <td>{{ $a->id }}</td>
    <td>{{ $a->name }}</td>
    <td>{{ $a->email }}</td>
    <td>
      {{-- 降格 --}}
      <form method="post" action="{{ route('admin.admins.demote',$a) }}" style="display:inline">
        @csrf @method('patch')
        <button {{ $admins->count() <= 1 ? 'disabled' : '' }}>降格</button>
      </form>
    </td>
  </tr>
  
  @endforeach
</table>
@endsection
