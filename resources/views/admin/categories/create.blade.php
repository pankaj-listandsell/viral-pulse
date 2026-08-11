@extends('layouts.admin')

@section('title', 'New category')
@section('heading', 'New category')

@section('content')
    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf
        @include('admin.categories.partials.form')
    </form>
@endsection
