@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">虛擬幣市價 : {{ $date_range }}</h2>
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
@foreach($coin_prices as $coin => $data)
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">{{ $coin }}</h2></div>
            <div class="card-block">
                <div class="flot-chart flot-line chart-{{ $coin }}"></div>
                <div class="flot-chart-legends flot-chart-legends--line chart-legend-{{ $coin }}"></div>
            </div>
        </div>
    </div>
</div>
@endforeach

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
    @foreach($coin_prices as $coin => $data)
    var {{ $coin }}Data = @json([$data]);
    $.plot($('.chart-{{ $coin }}'), {{ $coin }}Data, getLineChartOptions(ticks, 2, '.chart-legend-{{ $coin }}'));
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
            var url = '{{ route('admin.report.coin-prices') }}?' + $.param(param);
            window.location.href = url;
        }
    });

});
</script>
@endpush
