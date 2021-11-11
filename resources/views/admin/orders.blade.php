@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">訂單管理：</h2>
        </h2>
    </div>
</div>
<div class="card">
    <div class="card-block row">
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'from', 'class' => 'search-control', 'title' => 'From', 'value' => $from, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'to', 'class' => 'search-control', 'title' => 'To', 'value' => $to, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.select', ['name' => 'status', 'class' => 'search-control', 'title' => 'Order Status', 'value' => '', 'values' => $status])
        </div>
        <div class="col-sm-3">
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
                        <th>賣家</th>
                        <th>買家</th>
                        <th>幣別</th>
                        <th>數量</th>
                        <th>法幣</th>
                        <th>單價</th>
                        <th>手續費</th>
                        <th>利潤</th>
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
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/orders/list',
            data: {
                status: 'all',
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
                        .attr('href', '/admin/users/' + row.src_user_id)
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
                data: 'currency',
            },
            {
                data: 'unit_price',
            },
            {
                data: 'fee',
            },
            {
                data: 'profit',
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
                    if (data == null) {
                        return null;
                    }
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm');
                }
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            status: $('[name="status"]').val(),
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
        };
        if (moment(param.to).diff(moment(param.from), 'months') > 2) {
            swal({
                type: 'warning',
                title: '查詢範圍不可超過 3 個月',
            });
        } else {
            table.settings()[0].ajax.data = param;
            table
                .ajax
                .url('{{ route('admin.orders.list') }}')
                .load(null, false);
        }
    });

});
</script>
@endpush
