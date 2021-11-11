@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">充值管理：</h2>
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
            <table id="deposits" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>ID</th>
                        <th>Create Time</th>
                        <th>User Name</th>
                        <th>Coin</th>
                        <th>Amount</th>
                        <th>Confirmed Time</th>
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
    var table = $('#deposits').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/deposits/search/',
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/deposits/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'create_time',
            },
            {
                data: 'username',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/users/' + row.user_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'coin',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/accounts/' + row.account_id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'amount',
            },
            {
                data: 'confirm_time',
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
                .url('{{ route('admin.deposits.search') }}')
                .load(null, false);
        }
    });
});
</script>
@endpush
