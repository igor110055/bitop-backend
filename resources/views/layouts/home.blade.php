@extends('layouts.base')

@section('main')
<main class="main">
    @include('partials.header')
    @yield('content')
    @include('partials.footer')
</main>
@endsection
