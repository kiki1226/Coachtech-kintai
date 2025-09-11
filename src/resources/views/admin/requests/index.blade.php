@extends('layouts.admin')

@section('css')
<link rel="stylesheet"
      href="{{ asset('css/admin.requests.index.css') }}?v={{ filemtime(public_path('css/admin.requests.index.css')) }}">
@endsection

@section('title','管理者：申請一覧')

@section('content')
<div class="alert-success">
  <div class="alert-space">　</div>
  <h1 class="page-title">申請一覧</h1>
</div>

{{-- タブのみ --}}
<div class="req-toolbar">
  @php $tab = $tab ?? 'pending'; @endphp
  <div class="{{ $tab==='pending' ? 'is-active' : '' }}">
    <a href="{{ route('admin.requests.index', ['tab' => 'pending']) }}" class="req-month">承認待ち</a>
  </div>
  <div class="{{ $tab==='approved' ? 'is-active' : '' }}">
    <a href="{{ route('admin.requests.index', ['tab' => 'approved']) }}" class="req-month">承認済み</a>
  </div>
  <div class="{{ $tab==='rejected' ? 'is-active' : '' }}">
    <a href="{{ route('admin.requests.index', ['tab' => 'rejected']) }}" class="req-month">却下済み</a>
  </div>
</div>
<div class="req-tab-underline"></div>

<div class="card">
  <table class="req-table">
    <thead>
      <tr>
        <th>状態</th>
        <th>名前</th>
        <th>対象日時</th>
        <th>申請理由</th>
        <th>申請日時</th>
        <th class="text-right">詳細</th>
      </tr>
    </thead>
    <tbody>
      @forelse($requests as $r)
        <tr>
          <td>
            <span class="badge {{ $r->status }}">
              {{ \App\Models\AttendanceRequest::STATUS_LABELS[$r->status] ?? $r->status }}
            </span>
          </td>
          <td>{{ $r->user->name ?? '' }}</td>
          <td> {{ optional($r->target_date ?? $r->attendance?->work_date)->format('Y/m/d') }}</td>
          <td title="{{ $r->reason }}">{{ Str::limit($r->reason ?? '', 20) }}</td>
          <td>{{ optional($r->created_at)->format('Y/m/d') }}</td>
          <td class="text-right">
            <a class="btn btn-link" href="{{ route('admin.requests.show', $r) }}">詳細</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">データがありません。</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

{{-- ページネーション（必要なら） --}}
<div style="margin-top:12px;">
  {{ $requests->appends(['tab'=>$tab ?? 'pending'])->links() }}
</div>
@endsection
