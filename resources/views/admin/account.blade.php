@php
    use App\Models\Transaction;
@endphp
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">帳戶明細：<a href="{{ route('admin.users.show', ['user' => $user->id]) }}"
            >{{ $user->username }}</a> 的 {{ $account->coin }} 帳戶</h2>
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>
    @role('super-admin')
    <div class="card-block">
        <a
            href="{{ route('admin.accounts.manipulations.create', ['account' => $account->id]) }}"
            class="btn btn-primary waves-effect"
        >手動帳戶操作</a>
    </div>
    @endrole
    <div class="card-block row pb-0 pt-0">
        <div class="col-sm-4">
            <label class="mb-0">Account Balance</label>
            <h2 class="card-title">{{ $account->coin }} {{ formatted_coin_amount($account->balance, $account->coin) }}</h2>
        </div>
        <div class="col-sm-4">
            <label class="mb-0">Available Balance</label>
            <h2 class="card-title">{{ $account->coin }} {{ formatted_coin_amount($account->available_balance, $account->coin) }}</h2>
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
            url: '/admin/accounts/transactions/search/',
            data: {
                account_id: "{{ $account->id }}",
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
});
</script>
@endpush
