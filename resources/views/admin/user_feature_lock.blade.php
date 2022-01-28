@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">鎖定用戶功能：{{ $user->username }}</h2>
    </div>
</div>

<form method="post" action="{{ route('admin.users.lock', ['user' => $user->id]) }}">
{{ csrf_field() }}

    <div class="card">
        <div class="card-block">
            @include('widgets.forms.select', ['name' => 'type', 'value' => '', 'title' => '鎖定功能', 'values' => $types, 'required' => true, 'id' => 'types'])
            @include('widgets.forms.input', ['name' => 'expired_time' , 'title' => 'Expired Time', 'required' => true, 'class' => 'datetime-picker flatpickr-input active', 'placeholder' => 'Date &amp Time', 'id' => 'expired-time'])
            @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
            <a href="/admin/users/{{ $user->id }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>

</form>
@endsection

@push('scripts')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function () {

    $('#types').on('change', function (e) {
        var $type = $(this).val();
        $("#expired-time").prop('disabled', $type == 'admin');
    });
});

</script>
@endpush