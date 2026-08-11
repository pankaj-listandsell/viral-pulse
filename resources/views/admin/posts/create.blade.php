@extends('layouts.admin')

@section('title', 'New post')
@section('heading', 'New post')

@section('content')
    <form method="POST" action="{{ route('admin.posts.store') }}">
        @csrf
        @include('admin.posts.partials.form')
    </form>
@endsection
