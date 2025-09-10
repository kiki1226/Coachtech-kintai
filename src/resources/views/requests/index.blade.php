@extends('layouts.app')
@section('title','申請一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/requests.css') }}?v={{ filemtime(public_path('css/requests.css')) }}">
@endsection

@section('content')
@php use Illuminate\Support\Str; @endphp

<div class="req-wrap">
  <div class="alert-success">
    <div class="alert-space"></div>
    <h2 class="att-index__title">申請一覧</h2>
  </div>

  {{-- タブ --}}
<div class="req-toolbar">
    <div class="{{ $tab==='pending' ? 'is-active' : '' }}" >
      <a href="{{ route('requests.index', ['tab' => 'pending']) }}" class="req-month">承認待ち</a>
    </div>
    <div class="{{ $tab==='approved' ? 'is-active' : '' }}">
      <a href="{{ route('requests.index', ['tab' => 'approved']) }}" class="req-month">承認済み</a>
    </div>
</div>
  <div class="req-tab-underline"></div>

  <table class="req-table">
    <thead>
      <tr>
        <th>状態</th>
        <th>名前</th>
        <th>対象日付</th>
        <th>申請理由</th>
        <th>申請日時</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($requests as $r)
      @php
        // コントローラで with(['attendance.user']) 済み想定
        $att  = $r->attendance;
        $date = $att->work_date
                ?? $r->target_date
                ?? $r->date
                ?? $r->work_date
                ?? $r->target_day
                ?? null;

        // 表示名（本人の一覧なので attendance.user が無ければログインユーザー名でも可）
        $userName = $att->user->name ?? auth()->user()->name ?? '—';
      @endphp
      <tr>
        <td>{{ \App\Models\AttendanceRequest::STATUS_LABELS[$r->status] ?? $r->status }}</td>
        <td>{{ $userName }}</td>
        <td>{{ $date ? \Carbon\Carbon::parse($date)->locale('ja')->isoFormat('YYYY/M/D') : '—' }}</td>
        <td class="td-reason" title="{{ $r->reason }}">{{ Str::limit($r->reason, 30) }}</td>
        <td>{{ \Carbon\Carbon::parse($r->created_at)->locale('ja')->isoFormat('YYYY/M/D') }}</td>
        <td>
          @if ($att)
            {{-- 勤怠が紐づく場合は勤怠詳細へ --}}
            <a href="{{ route('attendance.show', ['attendance' => $att->id]) }}" class="link-detail">詳細</a>
          @else
            {{-- 紐づかない種類（例：休暇）は申請詳細へ --}}
            <a href="{{ route('requests.show', ['requestItem' => $r->id]) }}" class="link-detail">詳細</a>
          @endif
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">データはありません。</td></tr>
    @endforelse
    </tbody>
  </table>

  @if (method_exists($requests, 'links'))
    <div class="req-pagination">
      {{ $requests->appends(['tab' => $tab])->links() }}
    </div>
  @endif
</div>
@endsection
