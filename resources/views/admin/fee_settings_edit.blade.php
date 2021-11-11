@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            @isset($group)
            群組<a href="{{ route('admin.groups.show', ['group' => $group->id]) }}"> {{ $group->name }} </a>
            @endisset
            {{ $coin.__("messages.fee_setting.types.{$type}") }}
        </h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <form id="settings-form" method="post" action="/admin/fee-settings/">
            {{ csrf_field() }}
            {{ method_field('POST') }}

            @if (data_get($data, 'applicable_id'))
            <div class="mt-1 mb-4">
                <label class="custom-control custom-checkbox">
                    <input id="clear-all" type="checkbox" class="custom-control-input" value="1">
                    <span class="custom-control-indicator"></span>
                    <span class="custom-control-description">使用系統設定</span>
                </label>
            </div>
            @endif

            <div class="setting-toggle">
                <h3 class="card-block__title">金額範圍&amp;手續費設定</h3>
                <div class="table-responsive">
                    <table id="fee-settings" class="table table-bordered mt-0">
                        <thead class="thead-default">
                            <tr>
                                <th>範圍起始 ({{ $coin }})</th>
                                <th>範圍小於 ({{ $coin }})</th>
                                <th>手續費</th>
                                <th>單位</th>
                            </tr>
                        </thead>
                    </table>
                    <button id="new-row-btn" type="button" class="btn btn-primary btn-block">新增範圍</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendors/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="/vendors/bower_components/jszip/dist/jszip.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script>
$(function(){
    var table = $('#fee-settings').DataTable({
        searching: false,
        lengthChange: false,
        paging: false,
        info: false,
        ajax: {
            url: '/admin/fee-settings/data/',
            data: @json($data),
        },
        columns: [
            {
                data: 'range_start',
                className: 'start-cell',
                render: function (data, type, row, meta) {
                    return numberInput(meta.row, 'range_start', (data ? parseFloat(data) : ''), type);
                },
            },
            {
                data: 'range_end',
                render: function (data, type, row, meta) {
                    return numberInput(meta.row, 'range_end', (data ? parseFloat(data) : ''), type);
                },
            },
            {
                data: 'value',
                render: function (data, type, row, meta) {
                    return numberInput(meta.row, 'value', data, type);
                },
            },
            {
                data: 'unit',
                render: function (data, type, row, meta) {
                    return unitInput(meta.row, data);
                },
            },
        ],
        @if (data_get($data, 'applicable_id'))
        language: {
          emptyTable: "未設定，使用系統設定"
        },
        @endif
        "fnInitComplete": function() {
            @if (data_get($data, 'applicable_id'))
                closeTable(!table.data().count());
            @endif
        },
    });

    function numberInput(counter, name, value, type) {
        if (type == 'sort' || type == 'type') {
            return value;
        }
        return  '<div class="form-group mb-0">' +
                    '<input class="form-control '+ name +'" type="number" name="ranges['+ counter +']['+ name +']" value="'+ value +'" min="0"'+ ((name === 'range_start') ? ' disabled' : '') + ((name === 'value') ? ' step="0.000001" required' : '') +'>' +
                    '<i class="form-group__bar"></i>' +
                '</div>';
    }

    function unitInput(counter, value) {
        return  '<div class="form-check form-check-inline m-2">' +
                    '<label class="form-check-label">' +
                        '<input class="form-check-input unit" type="radio" name="ranges['+ counter +'][unit]" value="%" ' + ((value === '%')? 'checked' : '') + '> %' +
                    '</label>' +
                '</div>' +
                /* '<div class="form-check form-check-inline m-2">' +
                    '<label class="form-check-label">' +
                        '<input class="form-check-input unit" type="radio" name="ranges['+ counter +'][unit]" value="{{ $coin }}" ' + ((value === '{{ $coin }}')? 'checked' : '') + '> {{ $coin }}' +
                    '</label>' +
                '</div>' + */
                '<div class="float-right pt-2"><a href="#" class="row-close"><i class="zmdi zmdi-close"></i></div>';
    }

    $('#new-row-btn').click(function(){
        var endInput = $("#fee-settings .range_end").last();
        if (endInput.length) {
            if (!isNormalInteger(endInput.val())) {
                endInput.focus();
                return;
            }
            startVal = endInput.val();
        } else {
            startVal = 0;
        }
        table.row.add({
            range_start: startVal,
            range_end: '',
            value: '',
            unit: '%',
        }).draw();
    })

    function isNormalInteger(str) {
        return /^[1-9]\d*$/.test(str);
    }

    function checkRangesStart()
    {
      $("#fee-settings .range_end").each(function(index) {
        var endVal = $(this).val();
        if (isNormalInteger(endVal)) {
            var nextStartCell = $(this).closest('tr').next('tr').find('td.start-cell');
            if (nextStartCell.length) {
                table.cell(nextStartCell).data(endVal);
            }
        }
      });
    }

    $('#fee-settings').on("change input", "input.range_end", function() {
        newVal = $(this).val();
        startVal = $(this).closest('tr').find('.range_start').val();
        $(this).closest('.form-group').toggleClass('has-danger', ((parseInt(newVal) < parseInt(startVal)) || !isNormalInteger(newVal)));
        checkRangesStart();
    });

    $('#fee-settings').on('click', 'a.row-close', function(e) {
        e.preventDefault();
        table.row($(this).parents('tr')).remove().draw();
        checkRangesStart();
    });

    $("#settings-form").submit(function(e) {
        e.preventDefault();
        var formData = $('#settings-form').serialize();
        var data = $.param(@json($data));
        data = formData + '&' + data;
        console.log(data);
        $.ajax({
            type: 'POST',
            url: '/admin/fee-settings',
            data: data,
            dataType: 'json',
        }).done(function (data) {
            $.notify({
                message: '設定已更新'
            },{
                type: 'inverse'
            });
            table.ajax.reload();
        }).fail(function (err) {
            $.notify({
                message: 'Oops... there is something wrong! Please refresh the page.'
            },{
                type: 'danger'
            });
            table.ajax.reload();
        });
    });

    @if (data_get($data, 'applicable_id'))
    $('#clear-all').change(function() {
        closeTable(this.checked);
    });

    function closeTable(checked = true)
    {
        $('#clear-all').prop('checked', checked);
        $('.setting-toggle').toggleClass('d-none', checked);
        $('#fee-settings .range_end, #fee-settings .value, #fee-settings .unit').prop('disabled', checked);
    }
    @endif
});
</script>
@endpush
