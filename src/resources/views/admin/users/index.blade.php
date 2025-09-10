@extends('layouts.admin')
@section('css')
  <link rel="stylesheet" href="{{ asset('css/admin.user.index.css') }}?v={{ filemtime(public_path('css/admin.user.index.css')) }}">
@endsection
@section('title','スタッフ一覧')

@section('content')
<div class="alert-success">
  <div class="alert-space">　</div>
  <h1 class="page-title">スタッフ一覧</h1>
</div>
<div class="actions">
    <a href="{{ route('admin.admins.index') }}" class="btn btn-primary">
        管理者登録
    </a>
</div>
<div class="card" style="padding:0;">
  <table class="user-table">
    <thead>
      <tr>
        <th>名前</th>
        <th>メールアドレス</th>
        <th class="text-right">月次勤怠</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $u)
        <tr>
          <td>{{ $u->name }}</td>
          <td>{{ $u->email }}</td>
          <td class="text-right">
            <a class="btn btn-sm btn-outline"
               href="{{ route('admin.attendances.user', ['user' => $u->id]) }}">
              詳細
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="3">スタッフがいません。</td></tr>
      @endforelse
    </tbody>
  </table>
  
</div>

<div style="margin-top:12px;">
  {{ $users->links() }}
</div>
@endsection
