@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            銀行帳戶管理
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block row">
        <div class="col-sm-3">
            @include('widgets.forms.select', ['name' => 'status', 'class' => 'search-control', 'title' => '審核狀態', 'value' => (isset($status) ? $status : null), 'values' => __("messages.bank_account.status")])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="data" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>User</th>
                        <th>Bank</th>
                        <th>Account</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Id</th>
                        <th>User</th>
                        <th>Bank</th>
                        <th>Account</th>
                        <th>Status</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script>
$(function () {
    var table = $('#data').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/bank-accounts/search/',
            data: {
                @if (isset($status))
                    status: "{{ $status }}",
                @endif
            }
        },
        columns: [
            {
                data: 'id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(data)
                        .attr('href', '/admin/bank-accounts/' + row.id)
                        .prop('outerHTML');
                },
            },
            {
                data: 'user_id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(row.owner.username + '(' + row.user_id + ')')
                        .attr('href', '/admin/users/' + row.user_id)
                        .prop('outerHTML')
                        + '<br> 本名: ' + row.owner.name
                        + '<br> 國籍: ' + row.owner.nationality;
                },
            },
            {
                data: 'bank_id',
                render: function (data, type, row) {
                    return row.bank.nationality + '<br>'
                    + row.bank.name + '<br>'
                },
            },
            {
                data: 'account',
                render: function (data, type, row) {
                    return '戶名: ' + row.name + '<br>'
                        + '帳號: ' + row.account + '<br>'
                        + '幣別: ' + row.currency
                },
            },
            {
                data: 'id',
                render: function (data, type, row) {
                    var result = '';
                    if (!row.verified_at && !row.deleted_at) {
                        result += $('<span/>')
                            .text('{{ __("messages.bank_account.status.pending") }}')
                            .addClass('badge badge-pill badge-danger')
                            .prop('outerHTML');
                    }
                    if (row.deleted_at) {
                        result += $('<span/>')
                            .text('{{ __("messages.bank_account.status.deleted") }}')
                            .addClass('badge badge-pill badge-default')
                            .prop('outerHTML');
                    }
                    if (row.verified_at && !row.deleted_at) {
                        result += $('<span/>')
                            .text('{{ __("messages.bank_account.status.active") }}')
                            .addClass('badge badge-pill badge-success')
                            .prop('outerHTML');
                    }
                    return result;
                },
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var status = $('[name="status"] option:selected').val();
        var param = {
            "status": status,
        };
        table.settings()[0].ajax.data = param;
        table
            .ajax
            .url('/admin/bank-accounts/search/')
            .load(null, false);
    });
});
</script>
@endpush
