@php
    if (isset($user)) {
        $id = $user->id;
        $role = $user->username;
        $url = route('admin.users.limitations.store', ['user' => $user]);
    }
    elseif (isset($group)) {
        $id = $group->id;
        $role = $group->id;
        $url = route('admin.groups.limitations.store', ['group' => $group]);
    }
    else {
        $id = 'system';
        $role = 'System';
        $url = route('admin.limitations.store');
    }
@endphp
@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ $url }}">
    {{ csrf_field() }}
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="coin" value="{{ $coin }}">
    <input type="hidden" name="limitable" value="{{ $id }}">
    <div class="card">
        <div class="card-header"><h2 class="card-title">{{ $role }} {{ $coin }} {{ __("messages.limitation.types.{$type}") }}</h2></div>
        @if ($role !== 'System')
        <div class="card-block">
            <p>取消 {{ $role }} 設定</p>
            <div class="toggle-switch">
                @if (isset($active) and $active)
                <input type="checkbox" class="toggle-switch__checkbox" id="trigger">
                @else
                <input type="checkbox" class="toggle-switch__checkbox" id="trigger" disabled>
                @endif
                <i class="toggle-switch__helper"></i>
            </div>
        </div>
        @endif
        <div class="card-block">
            <div id='activation-toggle'>
            <input type="hidden" name="reset" value="">
            @include('widgets.forms.input', ['name' => 'min', 'class' => '', 'value' => data_get($limitation, 'min', 0), 'title' => '下限*', 'required' => true])
            @include('widgets.forms.input', ['name' => 'max', 'class' => '', 'value' => data_get($limitation, 'max', ''), 'title' => '上限*', 'required' => true])
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection
@push('scripts')
<script>
$(function () {
    $('#trigger').on('change', function () {
        var check = $('#trigger').prop('checked');
        $('#activation-toggle').toggleClass('d-none', check);
        $('[name="reset"]').prop('value', check);
    });
});
</script>
@endpush

