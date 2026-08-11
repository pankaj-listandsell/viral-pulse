@extends('layouts.admin')

@section('title', 'Edit category')
@section('heading', 'Edit category')
@section('subheading', $category->name)

@section('content')
    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
        @csrf
        @method('PUT')
        @include('admin.categories.partials.form')
    </form>
@endsection
