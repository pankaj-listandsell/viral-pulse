@extends('layouts.admin')

@section('title', 'Edit post')
@section('heading', 'Edit post')
@section('subheading', 'Last updated ' . $post->updated_at->diffForHumans())

@section('actions')
    <x-ui.button variant="secondary" :href="route('admin.posts.index')">
        <x-icon name="arrow-left" class="size-4" />
        All posts
    </x-ui.button>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.posts.update', $post) }}">
        @csrf
        @method('PUT')
        @include('admin.posts.partials.form')
    </form>
@endsection
