@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @isset ($user)
                <a href="{{ route('admin.users.show', ['user' => $user->id]) }}">{{ $user->username }}</a> 的廣告管理
            @else
                廣告管理
            @endif
        </h2>
    </div>
</div>
<div class="card">
    <div class="card-block row">
        <div class="col-sm-2">
        @include('widgets.forms.input', ['name' => 'from', 'class' => 'search-control', 'title' => 'From', 'value' => $from, 'type' => 'date'])
        </div>
        <div class="col-sm-2">
        @include('widgets.forms.input', ['name' => 'to', 'class' => 'search-control', 'title' => 'To', 'value' => $to, 'type' => 'date'])
        </div>
        <div class="col-sm-2">
        @include('widgets.forms.select', ['name' => 'coin', 'class' => '', 'values' => $coins, 'value' => '', 'title' => '幣別'])
        </div>
        <div class="col-sm-2">
        @include('widgets.forms.select', ['name' => 'status', 'class' => 'search-control', 'title' => 'Advertisement Status', 'value' => '', 'values' => $status])
        </div>
        <div class="col-sm-2">
        @include('widgets.forms.select', ['name' => 'is_express', 'class' => 'search-control', 'title' => '快捷/一般', 'value' => '', 'values' => $express])
        </div>
        <div class="col-sm-2">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="advertisements" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>生成時間</th>
                        <th>快捷</th>
                        @if (!isset($user))
                        <th>發佈者</th>
                        @endif
                        <th>廣告ID</th>
                        <th>類型</th>
                        <th>狀態</th>
                        <th>幣別</th>
                        <th>剩餘數量</th>
                        <th>法幣</th>
                        <th>單價</th>
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

    @if (!isset($user))
        var url = '/admin/advertisements/list';
    @else
        var url = '/admin/users/{{ $user->id }}/advertisements/search';
    @endif

    var col = [
        {
            data: 'created_at',
            render: function (data, type, row, meta) {
                return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
            }
        },
        {
            data: 'is_express',
            render: function (data, type, row, meta) {
                if (data) {
                    return '<i class="zmdi zmdi-check"></i>'
                }
                return '';
            },
        },
        {
            data: 'id',
            render: function (data, type, row, meta) {
                return $('<a/>')
                    .text(data)
                    .attr('href', '/admin/advertisements/' + data)
                    .prop('outerHTML');
            },
        },
        {
            data: 'type',
        },
        {
            data: 'status',
            render: function (data, type, row) {
                var info = {
                    'completed': { class: 'success', text: @json(__("messages.advertisement.status.completed")) },
                    'deleted': { class: 'danger', text: @json(__("messages.advertisement.status.deleted")) },
                    'available': { class: 'primary', text: @json(__("messages.advertisement.status.available")) },
                    'unavailable': { class: 'warning', text: @json(__("messages.advertisement.status.unavailable")) },
                };
                var i = info[row.status];
                return $('<span/>')
                    .text(i.text)
                    .addClass('badge badge-pill badge-' + i.class)
                    .prop('outerHTML');
            }
        },
        {
            data: 'coin',
        },
        {
            data: 'remaining_amount',
        },
        {
            data: 'currency',
        },
        {
            data: 'unit_price',
        },
    ];
    @if (!isset($user))
        col.splice(2, 0, {
            data: 'owner.username',
            render: function (data, type, row) {
                return $('<a/>')
                    .text(data)
                    .attr('href', '/admin/users/' + row.user_id)
                    .prop('outerHTML');
            },
        });
    @endif
    var table = $('#advertisements').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            url: url,
            data: {
                status: 'all',
                is_express: 'all',
                coin: 'All',
            }
        },
        columns: col,
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            status: $('[name="status"]').val(),
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
            is_express: $('[name="is_express"]').val(),
            coin: $('[name="coin"]').val(),
        };

        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url(url)
            .load(null, false);
    });

});
</script>
@endpush
