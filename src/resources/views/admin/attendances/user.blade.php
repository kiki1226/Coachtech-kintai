@extends('layouts.admin')
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin.attendance.user.css') }}?v={{ filemtime(public_path('css/admin.attendance.user.css')) }}">
@endsection
@section('title', $user->name.' さんの勤怠（管理）')

@section('content')
<div class="container">
  <div class="alert-success">
    <div class="alert-space">　</div>
    <h1 class="page-title">{{ $user->name }} さんの勤怠（{{ $month->format('Y年n月') }}）</h1>
  </div>

  {{-- 月ナビ --}}
  <div class="monthnav">
    <a class="secondary"href="{{ route('admin.attendances.user', ['user'=>$user->id, 'month'=>$prevMonth]) }}">
      <img src="{{ asset('products/image1.png') }}" alt="←">前月</a>

    <form method="GET" action="{{ route('admin.attendances.user', $user) }}" class="monthform">
      <input type="month" name="month" value="{{ $month->format('Y-m') }}">
    </form>

    <a class="secondary"
       href="{{ route('admin.attendances.user', ['user'=>$user->id, 'month'=>$nextMonth]) }}">翌月<img src="{{ asset('products/image1.png') }}" alt="→" class="img-right"></a>
  </div>

  {{-- 表 --}}
  <div class="card" style="padding:0;">
    <table class="month-table">
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th class="text-right">詳細</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr>
            <td>{{ $r['label'] }}</td>
            <td>{{ $r['start'] }}</td>
            <td>{{ $r['end'] }}</td>
            <td>{{ $r['break'] }}</td>
            <td>{{ $r['total'] }}</td>
            <td class="text-right">
              <a class="btn btn-sm btn-outline"
                 href="{{ route('admin.attendances.show', ['user'=>$user->id, 'date'=>$r['date']]) }}">詳細</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="actions">
    <a class="btn btn-dark"
       href="{{ route('admin.attendances.user', ['user'=>$user->id, 'month'=>$month->format('Y-m'), 'export'=>'csv']) }}">
      CSV出力
    </a>
  </div>
</div>
@endsection
@section('script')
<script>
  const m = document.querySelector('.monthform input[type="month"]');
  if (m) {
    m.addEventListener('change', () => {
      // 可能なら requestSubmit()、だめなら submit()
      if (m.form?.requestSubmit) m.form.requestSubmit();
      else m.form?.submit();
    });
  }
</script>
@endsection

