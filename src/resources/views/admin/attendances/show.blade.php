@extends('layouts.admin')
<link rel="stylesheet" href="{{ asset('css/admin.attendance.show.css') }}?v={{ filemtime(public_path('css/admin.attendance.show.css')) }}">

@section('title','勤怠詳細（管理者）')

@section('content')
<div class="container show">
  <div class="alert-success">
    <div class="alert-space">　</div>
    <h1 class="page-title">勤怠詳細</h1>
  </div>
    @if ($errors->any())
      <div class="alert alert-danger" style="margin-bottom:16px">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

   <div class="card show-card">
    {{-- ルート名は定義に合わせて（例：admin.attendances.updateDay でも可） --}}
    <form method="POST" action="{{ route('admin.attendances.update', ['user'=>$user->id,'date'=>$target->toDateString()]) }}">
      @csrf
      @method('PUT')

      <div class="row">
        <h6>名前</h6>
        <div class="chip-name">{{ $user->name }}</div>
      </div>

      <div class="row">
        <h6>日付</h6>
        <div class="chip-year">
          <div class="year">{{ $target->format('Y年') }}</div>
          <div class="day">{{ $target->format('n月j日') }}</div>
        </div>
      </div>

      {{-- 出勤・退勤 --}}
      <div class="row">
        <h6>出勤・退勤</h6>
        <div class="field">
          <div class="pair">
            <input class="time" type="time" name="clock_in_at"
                  value="{{ old('clock_in_at', optional($attendance?->clock_in_at)->format('H:i')) }}"
                  step="any">
            <span class="tilde">〜</span>
            <input class="time" type="time" name="clock_out_at"
                  value="{{ old('clock_out_at', optional($attendance?->clock_out_at)->format('H:i')) }}"
                  step="any">
          </div>
          <div class="errors">
            @error('clock_in_at')  <p class="error">{{ $message }}</p> @enderror
            @error('clock_out_at') <p class="error">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- 休憩1 --}}
      <div class="row">
        <h6>休憩</h6>
        <div class="field">
          <div class="pair">
            <input class="time" type="time" name="break_started_at"
                  value="{{ old('break_started_at', optional($attendance?->break_started_at)->format('H:i')) }}"
                  step="any">
            <span class="tilde">〜</span>
            <input class="time" type="time" name="break_ended_at"
                  value="{{ old('break_ended_at', optional($attendance?->break_ended_at)->format('H:i')) }}"
                  step="any">
          </div>
          <div class="errors">
            @error('break_started_at') <p class="error">{{ $message }}</p> @enderror
            @error('break_ended_at')   <p class="error">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- 休憩2 --}}
      <div class="row">
        <h6>休憩２</h6>
        <div class="field">
          <div class="pair">
            <input class="time" type="time" name="break2_started_at"
                  value="{{ old('break2_started_at', optional($attendance?->break2_started_at)->format('H:i')) }}"
                  step="any">
            <span class="tilde">〜</span>
            <input class="time" type="time" name="break2_ended_at"
                  value="{{ old('break2_ended_at', optional($attendance?->break2_ended_at)->format('H:i')) }}"
                  step="any">
          </div>
          <div class="errors">
            @error('break2_started_at') <p class="error">{{ $message }}</p> @enderror
            @error('break2_ended_at')   <p class="error">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- 備考 --}}
      <div class="row">
        <h6>備考</h6>
        <div class="field">
          <textarea name="note" class="note">{{ old('note', $attendance->note ?? '') }}</textarea>
          @error('note') <p class="error">{{ $message }}</p> @enderror
        </div>
      </div>

    </div>
    <div class="actions">
      <button class="btn-primary" type="submit">修正</button>
    </div>
  </form>

</div>
@endsection
