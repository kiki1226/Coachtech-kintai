@extends('layouts.admin')
@section('title', 'ダッシュボード')

@section('content')
  <div class="container">
    <h1 class="mb-4">管理ダッシュボード</h1>
    <p><a href="{{ route('admin.attendances.index') }}" class="btn btn-primary">勤怠一覧へ</a></p>
  </div>
@endsection
