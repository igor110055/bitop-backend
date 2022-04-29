@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @isset($user)
            <a href="{{ route('admin.users.show', ['user' => $user->id]) }}">{{ $user->username }}</a> 的訂單管理
            @else
            <h2 class="card-title">訂單管理</h2>
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
        @include('widgets.forms.select', ['name' => 'status', 'class' => 'search-control', 'title' => 'Order Status', 'value' => '', 'values' => $status])
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
            <table id="orders" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>生成時間</th>
                        <th>訂單號</th>
                        <th>快捷</th>
                        <th>賣家</th>
                        <th>買家</th>
                        <th>幣別</th>
                        <th>數量</th>
                        <th>總價</th>
                        <th>單價</th>
                        <th>狀態</th>
                        <th>完成時間</th>
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
    var table = $('#orders').DataTable({
        order: [[0, 'desc']],
        processing: true,
        serverSide: true,
        ajax: {
            @isset($user)
            url: '/admin/users/{{ $user->id }}/orders/search',
            @else
            url: '/admin/orders/list',
            @endif
            data: {
                status: 'all',
                is_express: 'all',
                coin: 'All',
            }
        },
        columns: [
            {
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                }
            },
            {
                data: 'id',
                render: function (data, type, row, meta) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/orders/' + data)
                        .prop('outerHTML');
                },
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
                data: 'src_user.username',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.src_user_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'dst_user.username',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.dst_user_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'coin',
            },
            {
                data: 'amount',
            },
            {
                data: 'total',
                render: function (data, type, row) {
                    return row.total + ' ' + row.currency;
                },
            },
            {
                data: 'unit_price',
            },
            {
                data: 'status',
                render: function (data, type, row) {
                    var info = {
                        'completed': { class: 'success', text: @json(__("messages.order.status.completed")) },
                        'canceled': { class: 'danger', text: @json(__("messages.order.status.canceled")) },
                        'processing': { class: 'primary', text: @json(__("messages.order.status.processing")) },
                        'claimed': { class: 'warning', text: @json(__("messages.order.status.claimed")) },
                    };
                    var i = info[row.status];
                    return $('<span/>')
                        .text(i.text)
                        .addClass('badge badge-pill badge-' + i.class)
                        .prop('outerHTML');
                }
            },
            {
                data: 'completed_at',
                render: function (data, type, row, meta) {
                    if (data) {
                        return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                    } else {
                        return '';
                    }
                }
            },
        ],
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
            @isset($user)
            .url('{{ route('admin.users.orders.search', ['user' => $user->id]) }}')
            @else
            .url('{{ route('admin.orders.list') }}')
            @endif
            .load(null, false);
    });

});
</script>
@endpush
