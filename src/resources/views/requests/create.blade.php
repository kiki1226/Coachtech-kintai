@extends('layouts.app')
@section('title','申請作成')

@section('content')
  <h2 class="header-title" style="margin-bottom:16px;">
    申請作成（{{ $type === 'edit' ? '勤務修正' : '休暇' }}）
  </h2>

  @if ($errors->any())
    <div class="alert alert-danger">
      @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('requests.store', $type) }}" style="max-width:480px;">
    @csrf

    <div style="margin-bottom:12px;">
      <label>対象日</label>
      <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required>
    </div>

    <div style="margin-bottom:12px;">
      <label>理由（任意）</label>
      <input type="text" name="reason" value="{{ old('reason') }}" placeholder="理由など">
    </div>

    <button class="btn primary" type="submit">送信</button>
    <a class="btn" href="{{ route('requests.index') }}" style="margin-left:8px;">戻る</a>
  </form>
@endsection
