@extends('layouts.admin')

@section('title','申請詳細（管理）')

@section('css')
<link rel="stylesheet"
      href="{{ asset('css/admin.requests.show.css') }}?v={{ filemtime(public_path('css/admin.requests.show.css')) }}">
@endsection

@section('content')
<div class="container">
  <div class="alert-success">
    <div class="alert-space">　</div>
    <h1 class="page-title">申請詳細</h1>
  </div>

  <div class="card">
    @php
      $ymd = optional($r->target_date ?? $a?->work_date)->format('Y年n月j日');
      $fmt = fn($dt) => optional($dt)->format('H:i') ?: '';
      $in  = $fmt(optional($a)->clock_in_at);
      $out = $fmt(optional($a)->clock_out_at);
      $b1s = $fmt(optional($a)->break_started_at);
      $b1e = $fmt(optional($a)->break_ended_at);
      $b2s = $fmt(optional($a)->break2_started_at ?? null);
      $b2e = $fmt(optional($a)->break2_ended_at ?? null);
      $note = $a->note ?? ($r->reason ?? '');
    @endphp
    <table class="table">
      <tbody>
      <tr>
        <th>名前</th>
        <td>{{ $r->user->name ?? '' }}</td>
      </tr>
      <tr>
        <th>日付</th>
        <td>{{ $ymd }}</td>
      </tr>
      <tr>
        <th>出勤・退勤</th>
        <td>
          <span class="time-view">{{ $in }}</span>
          <span class="tilde">〜</span>
          <span class="time-view">{{ $out }}</span>
        </td>
      </tr>
      <tr>
        <th>休憩</th>
        <td>
          <span class="time-view">{{ $b1s }}</span>
          <span class="tilde">〜</span>
          <span class="time-view">{{ $b1e }}</span>
        </td>
      </tr>
      <tr>
        <th>休憩２</th>
        <td>
          <span class="time-view">{{ $b2s }}</span>
          <span class="tilde">〜</span>
          <span class="time-view">{{ $b2e }}</span>
        </td>
      </tr>
      <tr>
        <th>備考</th>
        <td>{{ $note }}</td>
      </tr>
    </tbody>
  </table>

    {{-- フッターボタン：状態で出し分け --}}
   
    <div class="button">
        <a type="button" class="btn btn-outline-secondary"     onclick="history.back()">戻る</a>

        <div class="button-1">
        @if($r->status === 'pending')
          {{-- 却下ボタン --}}
          <form method="POST" action="{{ route('admin.requests.reject', ['request' => $r->id]) }}">
              @csrf
              <button type="submit" class="btn btn-danger">却下</button>
          </form>
          {{-- 承認 --}}
          <form method="POST" action="{{ route('admin.requests.approve', ['request' => $r->id]) }}">
            @csrf
            <button class="btn btn-dark">承認</button>
          </form>
        @elseif($r->status === 'approved')
          {{-- 申請済み → 承認済みタブの一覧へ --}}
          <a class="btn btn-zumi" href="{{ route('admin.requests.index', ['tab' => 'approved']) }}">申請済み</a>

        @elseif($r->status === 'rejected')
          <a class="btn btn-secondary" href="{{ route('admin.requests.index',['tab' => 'rejected']) }}">却下済み</a>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
