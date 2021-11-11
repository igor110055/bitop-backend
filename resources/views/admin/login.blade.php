@extends('layouts.home')

@section('title', config('app.name').' -- Login')
@section('content')
<div class="login">
    <div class="login__block active" id="l-login">
        <div class="login__block__header">
            <i class="zmdi zmdi-account-circle"></i>
            Hi there! Please Sign in
        </div>

        <form
            id='form-login'
            class="login__block__body"
            method="post"
            action="/admin/auth/login"
        >
            {{ csrf_field() }}

            <div class="form-group form-group--float form-group--centered">
                <input
                    id="email"
                    type="text"
                    class="form-control"
                    name="email"
                    value="{{ session('email', '') }}"
                >
                <label>Email Address</label>
                <i class="form-group__bar"></i>
            </div>

            <div class="form-group form-group--float form-group--centered">
                <input
                    id="password"
                    type="password"
                    class="form-control"
                     name="password"
                >
                <label>Password</label>
                <i class="form-group__bar"></i>
            </div>
            <div class="form-group form-group--float form-group--centered">
                <input class="form-control" name="code">
                <label>Two Factor Authentication</label>
                <i class="form-group__bar"></i>
            </div>
            @if (config('services.captcha.key'))
            <button id='button-submit' class="btn btn--icon login__block__btn h-captcha" data-sitekey="{{ config('services.captcha.key') }}" data-callback="onSubmit"><i class="zmdi zmdi-long-arrow-right"></i></button>
            @else
            <button id='button-submit' class="btn btn--icon login__block__btn"><i class="zmdi zmdi-long-arrow-right"></i></button>
            @endif
        </form>
        This site is protected by hCaptcha and its
        <a href="https://hcaptcha.com/privacy">Privacy Policy</a> and
        <a href="https://hcaptcha.com/terms">Terms of Service</a> apply.
    </div>
</div>
@endsection

@push('styles')
<style>
    div.login {
        min-height: calc(100vh - 200px);
        display: relative;
    }
</style>
@endpush

@push('scripts')

@if(env('HCAPTCHA_KEY'))
<script src="https://hcaptcha.com/1/api.js" async defer></script>
@endif

<script>

function onSubmit(token) {
    document.getElementById("form-login").submit();
};

$(function () {
@if(session('error'))
    $.notify({
        icon: 'fa fa-comments',
        title: 'Error!',
        message: '{{ session('error') }}',
        url: ''
    }, {
        element: 'body',
        type: 'danger',
        allow_dismiss: true,
        placement: { from: 'top', align: 'center' },
        offset: { x: 20, y: 20 },
        spacing: 10,
        z_index: 1031,
        delay: 2500,
        timer: 1000,
        url_target: '_blank',
        mouse_over: false,
        template:
            '<div data-notify="container" class="alert alert-dismissible alert-{0} alert--notify" role="alert">' +
                '<span data-notify="icon"></span> ' +
                '<span data-notify="title">{1}</span> ' +
                '<span data-notify="message">{2}</span>' +
            '</div>',
    });
@endif

});
</script>

<script src="/vendors/bower_components/remarkable-bootstrap-notify/dist/bootstrap-notify.min.js"></script>
@endpush
