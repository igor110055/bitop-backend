@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">訂單統計 : {{ $date_range }}</h2>
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
            <div class="card-header"><h2 class="card-title">訂單筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-order-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-order"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">訂單數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-order-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-order-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">訂單數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-order-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-price"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">分潤數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-share-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-share-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">分潤數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-share-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-share-price"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">獲利</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-profit"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-profit"></div>
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
    var orderCountData = @json($reports['order_count']);
    var orderAmountData = @json($reports['order_amount']);
    var orderPriceData = @json($reports['order_price']);
    var shareAmountData = @json($reports['share_amount']);
    var sharePriceData = @json($reports['share_price']);
    var profitData = @json($reports['profit']);

    // Create chart
    $.plot($('.chart-order-count'), orderCountData, getLineChartOptions(ticks, 0, '.chart-legend-order-count'));
    $.plot($('.chart-order-amount'), orderAmountData, getLineChartOptions(ticks, 6, '.chart-legend-order-amount'));
    $.plot($('.chart-order-price'), orderPriceData, getLineChartOptions(ticks, 2, '.chart-legend-order-price'));
    $.plot($('.chart-share-amount'), shareAmountData, getLineChartOptions(ticks, 6, '.chart-legend-share-amount'));
    $.plot($('.chart-share-price'), sharePriceData, getLineChartOptions(ticks, 2, '.chart-legend-share-price'));
    $.plot($('.chart-profit'), profitData, getLineChartOptions(ticks, 2, '.chart-legend-profit'));


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
            var url = '{{ route('admin.report.orders') }}?' + $.param(param);
            window.location.href = url;
        }
    });

});
</script>
@endpush
