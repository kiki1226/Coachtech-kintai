@extends('layouts.app')
@section('title','勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}?v={{ filemtime(public_path('css/attendance.css')) }}">
@endsection

@section('content')
<div class="att-index">

  <div class="alert-success">
    <div class="alert-space">　</div>
    <h2 class="att-index__title">勤怠一覧</h2>
  </div>

  {{-- 月移動ナビ --}}
  <div class="att-index__monthnav">
    {{-- 前月 --}}
    <a class="btn-ghost" href="{{ route('attendance.index', ['m' => $prevMonth]) }}">
      <img src="{{ asset('products/image1.png') }}" alt="←">  前月
    </a>
    {{-- 月見出し --}}
    <div class="att-index__month">
      <img src="{{ asset('products/calendar.png') }}" alt="カレンダー"> 
      {{ $base->isoFormat('YYYY年M月') }}
    </div>
    {{-- 翌月 --}}
    <a class="btn-ghost" href="{{ route('attendance.index', ['m' => $nextMonth]) }}">
      翌月
      <img src="{{ asset('products/image2.png') }}" alt="→" class="btn-ghost-img">
    </a>
  </div>

  <div class="att-index__tablewrap">
    <table class="att-table">
      <thead>
        <tr>

          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th class="text-left">詳細</th>
        </tr>
      </thead>
      <tbody>
      @php
        // 未定義でも落ちないように保険
        $attendances = $attendances ?? [];

        $tz      = config('app.timezone', 'Asia/Tokyo');
        $baseDay = $base instanceof \Carbon\CarbonInterface ? $base : \Carbon\Carbon::parse($base, $tz);
        $period  = \Carbon\CarbonPeriod::create($baseDay->copy()->startOfMonth(), $baseDay->copy()->endOfMonth());

        // 勤怠を 'Y-m-d' キーで引けるように
        $byDate = collect($attendances)->keyBy(function ($a) use ($tz) {
            $d = $a->work_date instanceof \Carbon\CarbonInterface
                ? $a->work_date->copy()->timezone($tz)
                : \Carbon\Carbon::parse($a->work_date, $tz);
            return $d->toDateString();
        });

        $holidayDates = $holidayDates ?? [];
      @endphp
      @foreach ($period as $d)  {{-- ← 日付で回す --}}
        @php
          $dateStr   = $d->toDateString();               // 例: 2025-09-01
          $att       = $byDate->get($dateStr);           // その日の勤怠 or null
          $isHoliday = in_array($dateStr, $holidayDates, true);
        @endphp

        <tr class="{{ $att ? '' : 'is-off' }}">
          {{-- 日付 --}}
          <td class="col-date">
            {{ $d->locale('ja')->isoFormat('M/D(ddd)') }}
            @if($isHoliday)
              <span class="badge-holiday">祝</span>
            @endif
          </td>

          {{-- 出勤 --}}
          <td class="ta-c">
            {{ $att && $att->clock_in_at ? $att->clock_in_at->timezone($tz)->format('H:i') : '' }}
          </td>

          {{-- 退勤 --}}
          <td class="ta-c">
            {{ $att && $att->clock_out_at ? $att->clock_out_at->timezone($tz)->format('H:i') : '' }}
          </td>

          {{-- 休憩（モデルのアクセサ） --}}
          <td class="ta-c">
            {{ $att?->break_hm ?? '' }}
          </td>

          {{-- 合計（モデルのアクセサ） --}}
          <td class="ta-c">
            {{ $att?->total_hm ?? '' }}
          </td>

          {{-- 詳細/編集 --}}
          <td class="ta-c">
            @php
              $ym = request('m');
              $isPending = $att
                ? \App\Models\AttendanceRequest::where('attendance_id', $att->id)
                    ->where('status', 'pending')
                    ->exists()
                : false;
            @endphp

            @if ($att)
              @if ($isPending)
                {{-- 申請中：編集不可の詳細へ（一般用） --}}
                <a class="btn btn-sm btn-outline"
                  href="{{ route('attendance.show', ['attendance' => $att->id, 'm' => $ym, 'from' => 'index']) }}">
                  詳細
                </a>
              @else
                {{-- 未申請：編集へ（一般用） --}}
                <a class="btn btn-sm btn-outline"
                  href="{{ route('attendance.edit', ['attendance' => $att->id, 'm' => $ym, 'from' => 'index']) }}">
                  詳細
                </a>
              @endif
            @else
              <span class="text-muted">詳細</span>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>

    </table>
  </div>

</div>
@endsection
