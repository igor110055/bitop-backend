@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">法幣資產報告 : {{ $page_title }}</h2>
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
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">法幣資產餘額</h2></div>
            @foreach ($data['balance'] as $currency => $balance)
            <div class="card-block">
                <h6>{{ $currency }}</h6>
                <div class="flot-chart flot-line chart-balance-{{ $currency }}"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-balance-{{ $currency }}"></div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">成本價格</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-unit-price }}"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-unit-price"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">交易金額</h2></div>
            @foreach ($data['trade_amount'] as $currency => $trade_amount)
            <div class="card-block">
                <h6>{{ $currency }}</h6>
                <div class="flot-chart flot-line chart-trade-amount-{{ $currency }}"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-trade-amount-{{ $currency }}"></div>
            </div>
            @endforeach
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
    @foreach($data['balance'] as $currency => $balance)
    var {{ $currency }}Balance = @json([$balance]);
    $.plot($('.chart-balance-{{ $currency }}'), {{ $currency }}Balance, getLineChartOptions(ticks, 0, '.chart-legend-balance-{{ $currency }}'));
    @endforeach

    var unitPrice = @json(array_values($data['unit_price']));
    $.plot($('.chart-unit-price'), unitPrice, getLineChartOptions(ticks, 0, '.chart-legend-unit-price'));

    @foreach($data['trade_amount'] as $currency => $trade_amount)
    var {{ $currency }}TradeAmount = @json($trade_amount);
    $.plot($('.chart-trade-amount-{{ $currency }}'), {{ $currency }}TradeAmount, getLineChartOptions(ticks, 0, '.chart-legend-trade-amount-{{ $currency }}'));
    @endforeach

    $('#search-submit').on('click', function (e) {
        var param = {
            from: $('[name="from"]').val(),
            to: $('[name="to"]').val(),
        };
        if (moment(param.to).diff(moment(param.from), 'days') > 99) {
            swal({
                type: 'warning',
                title: '查詢範圍不可超過 100 天',
            });
        } else {
            var url = '{{ route('admin.report.assets') }}?' + $.param(param);
            window.location.href = url;
        }
    });

});
</script>
@endpush
