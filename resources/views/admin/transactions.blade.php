@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">交易明細：</h2>
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
        @include('widgets.forms.select', ['name' => 'coin', 'class' => '', 'values' => $coins, 'value' => '', 'title' => '幣別'])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="transactions" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>ID</th>
                        <th>時間</th>
                        <th>餘額帳號</th>
                        <th>幣別</th>
                        <th>類型</th>
                        <th>數量</th>
                        <th>餘額</th>
                        <th>描述</th>
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
    var des_prefix = @json(__('messages.transaction.des_prefix'));
    var types = @json(__('messages.transaction.types'));
    var table = $('#transactions').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/transactions/search/',
            data: {
                coin: 'All',
            }
        },
        columns: [
            {
                data: 'id',
            },
            {
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {
                data: 'account_id',
                render: function (data, type, row, meta) {
                    return $('<a/>')
                            .text(row.user_text)
                            .attr('href', '/admin/users/'+row.account.user_id)
                            .prop('outerHTML')
                            + ' 的<br>'
                            + $('<a/>')
                            .text(row.coin + ' 帳號')
                            .attr('href', '/admin/accounts/'+data)
                            .prop('outerHTML');
                }
            },
            {
                data: 'coin',
            },
            {
                data: 'type',
                render: function (data, type, row) {
                    return types[data];
                },
            },
            {
                data: 'amount',
                render: function (data, type, row) {
                    return row.amount;
                },
            },
            {
                data: 'balance'
            },
            {
                data: 'type',
                render: function (data, type, row) {
                    if (row.link && row.text) {
                        return des_prefix[data] + $('<a/>')
                            .text(row.text)
                            .attr('href', row.link)
                            .prop('outerHTML');
                    } else {
                        return data;
                    }
                },
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
            coin: $('[name="coin"]').val(),
        };
        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url('{{ route('admin.transactions.search') }}')
            .load(null, false);
    });
});
</script>
@endpush
