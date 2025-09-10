@extends('layouts.app')
@section('title', '勤怠編集')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.edit.css') }}">
@endsection

@section('content')
  <div class="alert-success">
    <div class="alert-space">　</div>
    <h2 class="detail-title">勤怠編集</h2>
  </div>

  {{-- ▼バリデーション・フラッシュメッセージ --}}
  @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if (session('info'))    <div class="alert alert-info">{{ session('info') }}</div> @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('attendance.update', ['attendance' => $attendance->id]) }}">
    @csrf
    @method('PATCH')

    {{-- どこから来たか/戻り先の年月 --}}
    <input type="hidden" name="from" value="{{ request('from', 'attendance') }}">
    <input type="hidden" name="back" value="{{ request('m') }}">

    @php
      $tz = config('app.timezone', 'Asia/Tokyo');
      $wd = $attendance->work_date instanceof \Carbon\CarbonInterface
            ? $attendance->work_date
            : \Carbon\Carbon::parse($attendance->work_date);
    @endphp

    {{-- テーブル --}}
    <div class="detail-content">
      <div class="detail-row">
        <div class="label">名前</div>
        <div>{{ optional($attendance->user)->name }}</div>
      </div>

      <div class="detail-row">
        <div class="label">日付</div>
        <div class="date-split">
          <span class="date-y">{{ $wd->copy()->locale('ja')->isoFormat('YYYY年') }}</span>
          <span class="date-md">{{ $wd->copy()->locale('ja')->isoFormat('M月D日 (ddd)') }}</span>
        </div>
      </div>

      <div class="detail-row">
        <div class="label">出勤・退勤</div>
        <div>
          {{-- name をバリデーション名と合わせる（_atは使わない） --}}
          <input class="time-input" type="time" name="clock_in"
                 value="{{ old('clock_in', $attendance->clock_in_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          <span class="time-separator">〜</span>
          <input class="time-input" type="time" name="clock_out"
                 value="{{ old('clock_out', $attendance->clock_out_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          @error('clock_in')  <div class="error">{{ $message }}</div> @enderror
          @error('clock_out') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="detail-row">
        <div class="label">休憩1</div>
        <div>
          <input class="time-input" type="time" name="break1_start"
                 value="{{ old('break1_start', $attendance->break_started_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          <span class="time-separator">〜</span>
          <input class="time-input" type="time" name="break1_end"
                 value="{{ old('break1_end', $attendance->break_ended_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          @error('break1_start') <div class="error">{{ $message }}</div> @enderror
          @error('break1_end')   <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="detail-row">
        <div class="label">休憩2</div>
        <div>
          <input class="time-input" type="time" name="break2_start"
                 value="{{ old('break2_start', $attendance->break2_started_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          <span class="time-separator">〜</span>
          <input class="time-input" type="time" name="break2_end"
                 value="{{ old('break2_end', $attendance->break2_ended_at?->setTimezone($tz)?->format('H:i') ?? '') }}">
          @error('break2_start') <div class="error">{{ $message }}</div> @enderror
          @error('break2_end')   <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="detail-row">
        <div class="label">備考</div>
        <div>
          <textarea class="note-area" name="note" placeholder="メモなど">{{ old('note', $attendance->note) }}</textarea>
          @error('note') <div class="error">{{ $message }}</div> @enderror
        </div>
      </div>
    </div>

    {{-- アクション --}}
    <div class="actions">
      @php
        $from = request('from', 'attendance');
        $backYm = request('m');
        $backRoute = $from === 'requests'
            ? route('requests.index', ['m' => $backYm])
            : route('attendance.index', ['m' => $backYm]);
      @endphp

      <a href="{{ $backRoute }}" class="btn primary return">戻る</a>
      <button type="submit" class="btn primary">編集</button>
    </div>
  </form>
@endsection
