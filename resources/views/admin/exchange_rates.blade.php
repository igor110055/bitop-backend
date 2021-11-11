@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @isset($group)
            群組 <a href="{{ route('admin.groups.show', ['group' => $group->id]) }}">{{ $group->name }}</a> 匯率設定
            @else
            匯率設定
            @endisset
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="exchange-rates" class="table table-bordered">
                <thead class="thead-default">
                    <tr>
                        <th>幣別</th>
                        <th>匯率</th>
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
    var table = $('#exchange-rates').DataTable({
        ordering: false,
        searching: false,
        lengthChange: false,
        paging: false,
        info: false,
        ajax: '/admin/exchange-rates/get/{{ data_get($group, 'id', '') }}',
        columns: [
            {
                data: 'currency'
            },
            {
                data: 'rate',
                render: function (data, type, row) {
                    return row.is_editable ? adjustableField(row.currency, row.rate.bid, row.rate.ask) : staticField(row.rate.bid, row.rate.ask);
                },
            },
        ]
    });

    function adjustableField(currency, bid, ask) {
        return  '<div class="row">' +
            '<div class="col-lg-8">' +
                '<div class="form-group row mb-0">' +
                    '<label class="col-lg-2 col-form-label">BID</label>' +
                    '<div class="col-lg-10">' +
                        '<div class="form-group mb-0">' +
                            '<input class="bid adjustment-input form-control" type="number" value="'+ bid +'" min="0" step="0.000001">' +
                            '<i class="form-group__bar"></i>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="form-group row mb-0">' +
                    '<label class="col-lg-2 col-form-label">ASK</label>' +
                    '<div class="col-lg-10">' +
                        '<div class="form-group mb-0">' +
                            '<input class="ask adjustment-input form-control" type="number" value="'+ ask +'" min="0" step="0.000001">' +
                            '<i class="form-group__bar"></i>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<div class="col-lg-4 pt-4 text-center align-middle">' +
                '<button data-currency="' + currency + '" type="button" class="btn btn-outline-primary btn-sm waves-effect save d-none"><i class="zmdi zmdi-check"></i> SAVE </button>' +
            '</div>' +
        '</div>';
    }

    function staticField(bid, ask)
    {
        return  '<div class="row">' +
            '<div class="col-lg-8">' +
                '<div class="form-group row mb-0">' +
                    '<label class="col-lg-2 col-form-label">BID</label>' +
                    '<div class="col-lg-10">' +
                        '<p class="form-control-static">' +
                            bid +
                        '</p>' +
                    '</div>' +
                '</div>' +
                '<div class="form-group row mb-0">' +
                    '<label class="col-lg-2 col-form-label">ASK</label>' +
                    '<div class="col-lg-10">' +
                        '<p class="form-control-static">' +
                            ask +
                        '</p>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    $("#exchange-rates").on('click', '.save', function () {
        var currency = $(this).data('currency');
        var bid = $(this).closest('td').find('input.bid').val();
        var ask = $(this).closest('td').find('input.ask').val();
        var group_id = "{{ data_get($group, 'id', '') }}";
        var _token = "{{ csrf_token() }}";
        $.ajax({
            url: '/admin/exchange-rates/create',
            method: 'POST',
            data: { group_id: group_id, currency: currency, bid: bid, ask: ask, _token: _token },
            dataType: 'json',
        }).done(function () {
            $.notify({
                message: 'Exchange rate updated successfully.'
            },{
                type: 'inverse'
            });
            table.ajax.reload();
        }).fail(function (err) {
            if (err.responseJSON.errors.bid) {
                console.log(err.responseJSON.errors);
                err.responseJSON.errors.bid.forEach(function(error_message) {
                    $.notify({
                        message: error_message
                    },{
                        type: 'danger'
                    });
                });
            }
            $.notify({
                message: 'Oops... there is something wrong! Please refresh the page.'
            },{
                type: 'danger'
            });
            table.ajax.reload();
        })
    });

    $("#exchange-rates").on('change input', 'input.adjustment-input', function () {
        $(this).closest('td').find('.save').removeClass('d-none');
    });
});
</script>
@endpush
