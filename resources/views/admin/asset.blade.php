@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">資產明細：<a href="{{ route('admin.agencies.show', ['agency' => $agency->id]) }}"
            >{{ $agency->name }}</a> 的 {{ $asset->currency }} 資產</h2>
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>
    <div class="card-block">
        <a
            href="{{ route('admin.assets.manipulations.create', ['asset' => $asset->id]) }}"
            class="btn btn-primary waves-effect"
        >手動操作</a>
    </div>
    <div class="card-block row">
        <div class="col-sm-4">
            餘額 <span class="display-4"> {{ $asset->balance }} {{ $asset->currency }}</span>
        </div>
        <div class="col-sm-4">
            單價 <span class="display-4"> {{ $asset->unit_price }} {{ $base_currency }}</span>
        </div>
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
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="transactions" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>時間</th>
                        <th>類型</th>
                        <th>金額</th>
                        <th>餘額</th>
                        <th>輸入單價</th>
                        <th>單價</th>
                        <th>說明</th>
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
    var localizedTypes = @json(__('messages.asset_transaction.types'));
    var table = $('#transactions').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/assets/transactions',
            data: {
                id : "{{ $asset->id }}",
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
                data: 'type',
                render: function (data, type, row, meta) {
                    return localizedTypes[data];
                }
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
                data: 'unit_price'
            },
            {
                data: 'result_unit_price'
            },
            {
                data: 'transactable',
                render: function (data, type, row) {
                    if (row.transactable_type === 'Order') {
                        return '訂單 ' + $('<a/>')
                            .text(data.id)
                            .attr('href', '/admin/orders/' + data.id)
                            .prop('outerHTML');
                    }
                    if (row.transactable_type === 'Manipulation') {
                        st = '操作人 ' + $('<a/>')
                            .text(data.user.username)
                            .attr('href', '/admin/users/' + data.user_id)
                            .prop('outerHTML');
                        if (data.note) {
                            st = st + '，備註：' + data.note;
                        }
                        return st;
                    }
                },
            },
        ],
    });

    $('#search-submit').on('click', function (e) {
        var param = {
            id : "{{ $asset->id }}",
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
        };
        if (moment(param.to).diff(moment(param.from), 'months') > 2) {
            swal({
                type: 'warning',
                title: '查詢範圍不可超過 3 個月'+moment(param.to).diff(moment(param.from), 'months'),
            });
        } else {
            table.settings()[0].ajax.data = param;
            table
                .ajax
                .url('{{ route('admin.assets.transactions') }}')
                .load(null, false);
        }
    });

});
</script>
@endpush
