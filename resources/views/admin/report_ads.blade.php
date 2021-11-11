@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">廣告統計 : {{ $date_range }}</h2>
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
            <div class="card-header"><h2 class="card-title">廣告筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-ad-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-ad-count"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">買廣告筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-buy-ad-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-buy-ad-count"></div>
            </div>
            <div class="card-header"><h2 class="card-title">買廣告數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-buy-ad-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-buy-ad-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">買廣告數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-buy-ad-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-buy-ad-price"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">賣廣告筆數</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-sell-ad-count"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-sell-ad-count"></div>
            </div>
            <div class="card-header"><h2 class="card-title">賣廣告數量</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-sell-ad-amount"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-sell-ad-amount"></div>
            </div>
            <div class="card-header"><h2 class="card-title">賣廣告數量價值</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-sell-ad-price"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-sell-ad-price"></div>
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
    var adCountData = @json($reports['ad_count']);
    var buyAdCountData = @json($reports['buy_ad_count']);
    var buyAdAmountData = @json($reports['buy_ad_amount']);
    var buyAdPriceData = @json($reports['buy_ad_price']);
    var sellAdCountData = @json($reports['sell_ad_count']);
    var sellAdAmountData = @json($reports['sell_ad_amount']);
    var sellAdPriceData = @json($reports['sell_ad_price']);

    // Create chart
    $.plot($('.chart-ad-count'), adCountData, getLineChartOptions(ticks, 0, '.chart-legend-ad-count'));
    $.plot($('.chart-buy-ad-count'), buyAdCountData, getLineChartOptions(ticks, 0, '.chart-legend-buy-ad-count'));
    $.plot($('.chart-buy-ad-amount'), buyAdAmountData, getLineChartOptions(ticks, 2, '.chart-legend-buy-ad-amount'));
    $.plot($('.chart-buy-ad-price'), buyAdPriceData, getLineChartOptions(ticks, 6, '.chart-legend-buy-ad-price'));
    $.plot($('.chart-sell-ad-count'), sellAdCountData, getLineChartOptions(ticks, 2, '.chart-legend-sell-ad-count'));
    $.plot($('.chart-sell-ad-amount'), sellAdAmountData, getLineChartOptions(ticks, 6, '.chart-legend-sell-ad-amount'));
    $.plot($('.chart-sell-ad-price'), sellAdPriceData, getLineChartOptions(ticks, 2, '.chart-legend-sell-ad-price'));


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
            var url = '{{ route('admin.report.ads') }}?' + $.param(param);
            window.location.href = url;
        }
    });

});
</script>
@endpush
