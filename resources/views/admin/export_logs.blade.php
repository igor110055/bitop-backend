@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush
@extends('layouts.main')
@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">傳送至外部之交易明細：</h2>
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
                        <th>SerialNumber</th>
                        <th>LoginID</th>
                        <th>事件</th>
                        <th>Account</th>
                        <th>Amount</th>
                        <th>cfee</th>
                        <th>type</th>
                        <th>coin</th>
                        <th>BankcFee</th>
                        <th>ConfirmTime</th>
                        <th>發送時間</th>
                        <th>操作</th>
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

    var table = $('#logs').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: {
            url: '/admin/export_logs/search/',
        },
        columns: [
            {
                data: 'id',
            },
            {
                data: 'user_id',
                render: function (data, type, row) {
                    return $('<a/>')
                        .text(row.user_id)
                        .attr('href', '/admin/users/' + row.user_id)
                        .prop('outerHTML')
                },
            },
            {
                data: 'loggable_type',
                render: function (data, type, row) {
                    var url;
                    if (data === 'Order') {
                        url = '/admin/orders/'+row.loggable_id;
                    } else if (data === 'Withdrawal') {
                        url = '/admin/withdrawals/'+row.loggable_id;
                    } else if (data === 'Deposit') {
                        url = '/admin/deposits/'+row.loggable_id;
                    }
                    return $('<a/>')
                        .text(data + ' ' + row.loggable_id)
                        .attr('href', url)
                        .prop('outerHTML')
                },
            },
            {
                data: 'account',
            },
            {
                data: 'amount',
            },
            {
                data: 'c_fee',
            },
            {
                data: 'type',
            },
            {
                data: 'coin',
            },
            {
                data: 'bankc_fee',
            },
            {
                data: 'created_at',
                render: function (data, type, row, meta) {
                    return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            {
                data: 'submitted_at',
                render: function (data, type, row, meta) {
                    if (data) {
                        return moment(data).utcOffset(timezoneUtcOffset).format('YYYY-MM-DD HH:mm:ss');
                    } else {
                        return '';
                    }
                }
            },
            {
                data: 'id',
                render: function (data, type, row, meta) {
                    return '<a href="#" class="resubmit" data-id="' + data + '">重送</a>';
                }
            }
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
            .url('{{ route('admin.export_logs.search') }}')
            .load(null, false);
    });

    $('#logs').on('click', '.resubmit', function(e) {
        e.preventDefault();
        var id = $(this).attr("data-id");
        console.log(id);

        $.ajax({
            type: 'POST',
            url: '{{ route('admin.export_logs.submit') }}',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
            },
            dataType: 'json',
        }).done(function () {
            $.notify({
                message: '重送完成'
            },{
                type: 'inverse'
            });
        }).fail(function (err) {
            console.log(err);
            var message = err.responseJSON.message;
            if (message) {
                $.notify({ message: message }, { type: 'danger' });
            } else {
                $.notify({ message: '重送失敗，請聯繫工程人員' }, { type: 'danger' });
            }
        })
    });

});
</script>
@endpush
