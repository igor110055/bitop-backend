@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <a href="{{ route('admin.users.show', ['user' => $user->id]) }}">{{ $user->username }}</a> 的安全性紀錄
        </h2>
    </div>

    <div class="card-block row">
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'from', 'class' => 'search-control', 'title' => 'From', 'value' => $from, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'to', 'class' => 'search-control', 'title' => 'To', 'value' => $to, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="logs" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Time</th>
                        <th>Event</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="/vendors/bower_components/moment/min/moment.min.js"></script>
<script>

$(function () {
    var timezoneUtcOffset = {{ config('core.timezone_utc_offset.default') }};
    var messages = {!! json_encode(__('messages.user.log_message')) !!};
    var table = $('#logs').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: '/admin/users/{{ $user->id }}/logs/search/',
        },
        columns: [
            {
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {
                data: 'message',
                render: function (data, type, row) {
                    return messages[data];
                },
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
        };
        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url('{{ route('admin.user_logs.search', ['user' => $user->id]) }}')
            .load(null, false);
    });

});
</script>
@endpush
