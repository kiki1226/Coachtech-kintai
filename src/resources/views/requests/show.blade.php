{{-- resources/views/requests/show.blade.php (抜粋) --}}
@extends('layouts.app')
  <link rel="stylesheet" href="{{ asset('css/requests.show.css') }}?v={{ filemtime(public_path('css/requests.show.css')) }}">
@section('title','申請詳細')

@section('content')
  <section class="content">
    <div class="card" style="padding:24px;">
      <h2 style="font-size:18px; margin-bottom:16px;">申請詳細</h2>
      <dl style="display:grid; grid-template-columns:120px 1fr; gap:8px 16px;">
        <dt>状態</dt>
        <dd>{{ \App\Models\AttendanceRequest::STATUS_LABELS[$requestItem->status] ?? $requestItem->status }}</dd>

        <dt>名前</dt>
        <dd>{{ $requestItem->user?->name ?? '—' }}</dd>

        <dt>対象日付</dt>
        <dd>{{ \Carbon\Carbon::parse($requestItem->target_date ?? $requestItem->date ?? $requestItem->work_date ?? $requestItem->target_day)->isoFormat('YYYY/MM/DD (ddd)') }}</dd>

        <dt>申請理由</dt>
        <dd>{{ $requestItem->reason }}</dd>

        <dt>申請日時</dt>
        <dd>{{ \Carbon\Carbon::parse($requestItem->created_at)->isoFormat('YYYY/MM/DD HH:mm') }}</dd>
        
      </dl>
      <p style="margin-top:16px;"><a href="{{ route('requests.index') }}">一覧へ戻る</a></p>
    </div>
  </section>
@endsection
