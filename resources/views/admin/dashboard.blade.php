@extends('admin.layouts.app')

@section('title', 'لوحة التحكم')

@section('breadcrumb')
<li class="inline-flex items-center">
    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm text-gray-700 hover:text-blue-600">
        <i class="fas fa-home ml-2"></i>
        لوحة التحكم
    </a>
</li>
@endsection

@section('content')


@endsection