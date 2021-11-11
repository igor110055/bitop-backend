@extends('layouts.main')

@section('content')
<div class="row quick-stats">

    <a href="{{ route('admin.users.index', ['status' => 'processing']) }}" class="col-sm-6 col-md-3">
        <div class="quick-stats__item bg-blue">
            <div class="quick-stats__info">
                <h2>{{ $user_count }}</h2>
                <small>實名驗證待審查</small>
            </div>
        </div>
    </a>

    <a href="{{ route('admin.bank-accounts.index', ['status' => 'pending']) }}" class="col-sm-6 col-md-3">
        <div class="quick-stats__item bg-blue">
            <div class="quick-stats__info">
                <h2>{{ $bank_account_pending_count }}</h2>
                <small>銀行帳戶待審查</small>
            </div>
        </div>
    </a>

    <a href="{{ route('admin.groups.applications')}}" class="col-sm-6 col-md-3">
        <div class="quick-stats__item bg-blue">
            <div class="quick-stats__info">
                <h2>{{ $group_application_count }}</h2>
                <small>申請群組待審查</small>
            </div>
        </div>
    </a>
</div>
<div class="content__title">
    <h1>系統資產</h1>
</div>
<div class="row quick-stats">
    @foreach ($assets_balance as $currency => $balance)
    <a href="{{ route('admin.report.assets') }}" class="col-sm-6 col-md-3">
        <div class="quick-stats__item
        @if ((int)$balance < 0)
            bg-red
        @else
            bg-green
        @endif
        ">
            <div class="quick-stats__info">
                <h2>{{ $balance.' '.$currency }}</h2>
                <small>系統 {{ $currency }} 資產總額</small>
            </div>

            <div class="quick-stats__chart sparkline-bar-stats">{{ $assets_balance_history[$currency] }}</div>
        </div>
    </a>
    @endforeach
</div>

<div class="content__title">
    <h1>系統內帳號虛擬幣總額</h1>
</div>
<div class="row stats">
    @foreach ($coin_balances as $coin => $coin_balance)
    <div class="col-sm-6 col-md-3">
        <div class="stats__item">
            <div class="stats__chart bg-{{ get_color_class($coin) }}">
                <div class="flot-chart flot-line flot-chart--xs chart-balance-{{ $coin }}"></div>
            </div>

            <div class="stats__info">
                <div>
                    <h2>{{ formatted_coin_amount($coin_balance, $coin).' '.$coin }}</h2>
                    <small><a href="{{ route('admin.report.accounts') }}">詳細資料</a></small>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="content__title">
    <h1>系統錢包虛擬幣總額</h1>
</div>
<div class="row stats">
    @foreach ($wallet_balances as $coin => $wallet_balance)
    <div class="col-sm-6 col-md-3">
        <div class="stats__item">
            <div class="stats__chart bg-{{ get_color_class($coin) }}">
                <div class="flot-chart flot-line flot-chart--xs chart-wallet-balance-{{ $coin }}"></div>
            </div>

            <div class="stats__info">
                <div>
                    <h2>{{ formatted_coin_amount($wallet_balance, $coin).' '.$coin }}</h2>
                    <small><a href="{{ route('admin.report.wallet-balances') }}">詳細資料</a></small>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="content__title">
    <h1>價格</h1>
</div>
<div class="row">
        <div class="col-12 col-lg-6">
            <div class="card card-inverse widget-past-days">
                <div class="card-header">
                    <h2 class="card-title">法幣報價 <small><a href="{{ route('admin.report.exchange-rates') }}" class="text-white">歷史紀錄</a></small></h2>
                </div>
                <div class="listview listview--inverse listview--striped">
                    @foreach($currency_exchange_rates as $currency => $rates)
                    <a href="{{ route('admin.report.exchange-rates') }}" class="listview__item d-block">
                        <div class="row">
                            <div class="widget-past-days__info col-3">
                                <h3 class="pt-3">{{ $currency }}</h3>
                            </div>
                            <div class="widget-past-days__info col-3">
                                <small>bid</small>
                                <h3>{{ $rates['bid'] }}</h3>
                            </div>
                            <div class="widget-past-days__info col-3">
                                <small>ask</small>
                                <h3>{{ $rates['ask'] }}</h3>
                            </div>
                            <div class="widget-past-days__chart col-3">
                                <div class="sparkline-bar-stats">{{ $currency_exchange_rate_history[$currency] }}</div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card card-inverse widget-profile">
                <div class="card-header text-left">
                    <h2 class="card-title">虛擬幣市場價格 <small><a href="{{ route('admin.report.coin-prices') }}" class="text-white">歷史紀錄</a></small></h2>
                </div>
                <div class="listview listview--inverse listview--striped">
                    @foreach($coin_prices as $coin => $price)
                    <a href="{{ route('admin.report.coin-prices') }}" class="listview__item d-block">
                        <div class="row">
                            <div class="widget-past-days__info col-3">
                                <h3 class="pt-3">{{ $coin }}</h3>
                            </div>
                            <div class="widget-past-days__info col-3">
                                <small>價格</small>
                                <h3>{{ $price['price'] }}</h3>
                            </div>
                            <div class="widget-past-days__chart col-3">
                                <div class="sparkline-bar-stats">{{ $coin_price_history[$coin] }}</div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script src="/vendors/bower_components/flot/jquery.flot.js"></script>
<script src="/vendors/bower_components/flot/jquery.flot.resize.js"></script>
<script src="/vendors/bower_components/flot.curvedlines/curvedLines.js"></script>
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="/vendors/bower_components/moment/min/moment.min.js"></script>
<script src="/vendors/bower_components/jquery-sparkline/dist/jquery.sparkline.min.js"></script>
<script>
$(function () {
    @include('scripts.line_chart_options')
    @include('scripts.line_chart_tooltip')

    // ticks of dates
    var ticks = @json($ticks);

    // Stats Charts
    var statsChartOptions = {
        series: {
            shadowSize: 0,
            lines: {
                fill: true,
                fillColor: "rgba(255, 255, 255, 0.5)"
            }
        },
        grid: {
            borderWidth: 0,
            labelMargin:10,
            hoverable: true,
            clickable: true,
            mouseActiveRadius:6

        },
        xaxis: {
            ticks: false,
        },
        yaxis: {
            ticks: false
        },
        legend: {
            show: false
        }
    };

    @foreach($balance_chart_data['balance'] as $coin => $balance)
    let {{ str_replace('-', '', $coin) }}Balance = [{
        label: "{{ $coin.' balance' }}",
        data: @json($balance['data'])
    }];
    $.plot($('.chart-balance-{{ $coin }}'), {{ str_replace('-', '', $coin) }}Balance, statsChartOptions);
    @endforeach

    @foreach($wallet_balance_chart_data['balance'] as $coin => $balance)
    let {{ str_replace('-', '', $coin) }}WalletBalance = [{
        label: "{{ $coin.' balance' }}",
        data: @json($balance['data'])
    }];
    $.plot($('.chart-wallet-balance-{{ $coin }}'), {{ str_replace('-', '', $coin) }}WalletBalance, statsChartOptions);
    @endforeach

    if($('.sparkline-bar-stats')[0]) {
        $('.sparkline-bar-stats').sparkline('html', {
            type: 'bar',
            height: 36,
            barWidth: 3,
            barColor: '#fff',
            barSpacing: 2
        });
    }

});
</script>
@endpush
