@extends('layouts.base')

@section('main')
<main class="main">
    @include('partials.pageloader')
    @include('partials.header')
    @include('partials.sidebar')
    <section class="content">
        @yield('content')
    </section>
    @include('partials.footer')
</main>
@endsection

@push('styles')
<style>
    section.content {
        min-height: calc(100vh - 200px);
    }
</style>
@endpush

@push('scripts')
@if ($flash_message = session('flash_message'))
    <script>
        $(function(){
            $.notify({
                @if (data_get($flash_message, 'unescaped'))
                    message: '{!! $flash_message['message'] !!}'
                @else
                    message: '{{ $flash_message['message'] }}'
                @endif
            },{
                type: '{{ data_get($flash_message, 'class', 'inverse') }}',
                delay: 0,
            });
        });
    </script>
@endif
@endpush
