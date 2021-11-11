@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">提現充值統計 : {{ $date_range }}</h2>
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
            @include('widgets.forms.select', ['name' => 'group_id', 'value' => $current_group, 'title' => '所屬群組', 'values' => $groups, 'required' => true])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">Submit</button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">提現筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-withdrawal-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-withdrawal-count"></div>
            </div>
            <div class="card-header"><h2 class="card-title">提現數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-withdrawal-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-withdrawal-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">提現數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-withdrawal-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-withdrawal-price"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">充值筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-deposit-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-deposit-count"></div>
            </div>
            <div class="card-header"><h2 class="card-title">充值數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-deposit-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-deposit-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">充值數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-deposit-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-deposit-price"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script src="/vendors/bower_components/flot/jquery.flot.js"></script>
<script src="/vendors/bower_components/flot/jquery.flot.resize.js"></script>
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="/vendors/bower_components/moment/min/moment.min.js"></script>
<script>
$(function () {
    @include('scripts.line_chart_options')
    @include('scripts.line_chart_tooltip')

    // ticks of dates
    var ticks = @json($ticks);

    // Chart Data
    var withdrawalCountData = @json($reports['withdrawal_count']);
    var withdrawalAmountData = @json($reports['withdrawal_amount']);
    var withdrawalPriceData = @json($reports['withdrawal_price']);
    var depositCountData = @json($reports['deposit_count']);
    var depositAmountData = @json($reports['deposit_amount']);
    var depositPriceData = @json($reports['deposit_price']);

    // Create chart
    $.plot($('.chart-withdrawal-count'), withdrawalCountData, getLineChartOptions(ticks, 0, '.chart-legend-withdrawal-count'));
    $.plot($('.chart-withdrawal-amount'), withdrawalAmountData, getLineChartOptions(ticks, 6, '.chart-legend-withdrawal-amount'));
    $.plot($('.chart-withdrawal-price'), withdrawalPriceData, getLineChartOptions(ticks, 2, '.chart-legend-withdrawal-price'));
    $.plot($('.chart-deposit-count'), depositCountData, getLineChartOptions(ticks, 0, '.chart-legend-deposit-count'));
    $.plot($('.chart-deposit-amount'), depositAmountData, getLineChartOptions(ticks, 6, '.chart-legend-deposit-amount'));
    $.plot($('.chart-deposit-price'), depositPriceData, getLineChartOptions(ticks, 2, '.chart-legend-deposit-price'));


    $('#search-submit').on('click', function (e) {
        var group_id = $('[name="group_id"]').val();
        if (group_id === 'system') {
            group_id = null;
        }

        var param = {
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
            group_id: group_id,
        };
        if (moment(param.to).diff(moment(param.from), 'days') > 99) {
            swal({
                type: 'warning',
                title: '查詢範圍不可超過 100 天',
            });
        } else {
            var url = '{{ route('admin.report.withdrawals-deposits') }}?' + $.param(param);
            window.location.href = url;
        }
    });

});
</script>
@endpush
