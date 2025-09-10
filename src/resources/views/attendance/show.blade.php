@extends('layouts.app')
@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.show.css') }}">
@endsection

@section('content')
<div class="detail-card">
    <div class="alert-success">
        <div class="alert-space">　</div>
        <h2 class="detail-title">勤怠詳細(申請中)</h2>
    </div>

    <div class="detail-content">
        <div class="detail-row">
            <div class="label">名前</div>
            <div>
                {{-- PHP8 の null セーフ演算子 or optional() どちらでもOK --}}
                {{ $attendance->user?->name ?? auth()->user()->name ?? '—' }}
                {{-- もしくは： {{ optional($attendance->user)->name ?? '—' }} --}}
            </div>
        </div>
        <div class="detail-row">
            <div class="label">日付</div>
                @php
                    // 時刻表示を H:i にそろえるヘルパ
                    $tz  = config('app.timezone', 'Asia/Tokyo');
                    $fmt = function ($v) use ($tz) {
                        if ($v instanceof \Carbon\CarbonInterface) {
                            return $v->setTimezone($tz)->format('H:i');
                        }
                        if (empty($v)) return '';
                        try {
                            return \Carbon\Carbon::parse($v)->setTimezone($tz)->format('H:i');
                        } catch (\Throwable $e) {
                            return '';
                        }
                    };

                    // 見出しの年月日表示用
                    $d = $display->work_date instanceof \Carbon\CarbonInterface
                        ? $display->work_date->copy()->locale('ja')
                        : \Carbon\Carbon::parse($display->work_date)->locale('ja');
                @endphp
                <div class="date-split">
                    <span class="date-y">{{ $d?->isoFormat('YYYY年') }}</span>
                    <span class="date-md">{{ $d?->isoFormat('M月D日 (ddd)') }}</span>
                </div>
        </div>
        <div class="detail-row">
            <div class="label">出勤・退勤</div>
            <span class="date-syu">{{ $fmt($display->clock_in_at) }}</span>
            <span class="date-kara">〜</span>
            <span class="date-tai">{{ $fmt($display->clock_out_at) }}</span>    
        </div>

        <div class="detail-row">
            <div class="label">休憩</div>
            <span class="date-kyu">{{ $fmt($display->break_started_at) }}</span>
            <span class="date-kara">〜</span>
            <span class="date-kei">{{ $fmt($display->break_ended_at) }}</span>
        </div>

        <div class="detail-row">
            <div class="label">休憩2</div>
            <span class="date-kyu">{{ $fmt($display->break2_started_at) }}</span>
            <span class="date-kara">〜</span>
            <span class="date-kei">{{ $fmt($display->break2_ended_at) }}</span>
        </div>

        {{-- 備考 --}}
        <div class="detail-row">
            <div class="label">備考</div>
            <span class="date-biko">{{ $display->note }}</span>
        </div>
    </div>
    <div class="detail-actions">    
       @if ($attendance->status === 'pending')
        <span class="badge">申請中</span>
        {{-- 編集ボタンは出さない / disabled 等 --}}
        @else
        <a href="{{ route('attendance.index', ['attendance' => $attendance->id, 'm' => request('m')]) }}"
            class="btn">承認待ちのため修正できません</a>
        @endif
    </div>
</div>

@endsection
