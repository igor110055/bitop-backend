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
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="transactions" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Description</th>
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
    var des_prefix = @json(__('messages.transaction.des_prefix'));
    var types = @json(__('messages.transaction.types'));
    console.log(types);
    var table = $('#transactions').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/transactions/search/',
        },
        columns: [
            {
                data: 'id',
            },
            {
                data: 'time',
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
        };
        if (moment(param.to).diff(moment(param.from), 'days') > 10) {
            swal({
                type: 'warning',
                title: '查詢範圍不可超過 10 天',
            });
        } else {
            table.settings()[0].ajax.data = param;
            table
                .ajax
                .url('{{ route('admin.transactions.search') }}')
                .load(null, false);
        }
    });
});
</script>
@endpush
