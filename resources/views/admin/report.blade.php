@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <h2 class="card-title">日報表 : {{ $date }}</h2>
        </h2>
    </div>
    <div class="card-block row">
        <div class="col-sm-3">
            <a
                href="{{ route('admin.report.daily', ['date' => $yesterday]) }}"
                class="btn btn-primary waves-effect mt-4"
            ><i class="zmdi zmdi-chevron-left"></i> 前一日</a>
            @if($tomorrow)
            <a
                href="{{ route('admin.report.daily', ['date' => $tomorrow]) }}"
                class="btn btn-primary waves-effect mt-4"
            >後一日 <i class="zmdi zmdi-chevron-right"></i></a>
            @endif
        </div>
        <div class="col-sm-3">
        @include('widgets.forms.input', ['name' => 'date', 'class' => 'search-control', 'title' => '前往日期', 'value' => $date, 'type' => 'date'])
        </div>
        <div class="col-sm-3">
            <button class="btn btn-primary mt-4" id="search-submit" name="submit" value="1">前往</button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">當日最後虛擬幣報價 (Base currency)</h2></div>
            <div class="card-block">
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coin_prices as $coin => $prices)
                            @if(!empty($prices))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ data_get($prices, 'price') }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <a href="{{ route('admin.report.coin-prices') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">系統帳號內虛擬幣數量統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>數量</th>
                            <th>數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($account_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['balance'] ? formatted_coin_amount($report[$group]['balance']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['balance_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.accounts') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

@role('super-admin')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">系統錢包內虛擬幣數量統計</h2></div>
            <div class="card-block">
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>數量</th>
                            <th>數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($wallet_balance_report as $coin => $report)
                            @if(!empty($report))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report['exchange_rate'] }}</td>
                                <td>{{ $report['balance'] ? formatted_coin_amount($report['balance']).' '.$coin : null }}</td>
                                <td>{{ $report['balance_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <a href="{{ route('admin.report.wallet-balances') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>
@endrole

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">訂單統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>訂單筆數</th>
                            <th>交易數量</th>
                            <th>交易數量價值</th>
                            <th>分潤數量</th>
                            <th>分潤數量價值</th>
                            <th>獲利</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['order_count'] }}</td>
                                <td>{{ $report[$group]['order_amount'] ? formatted_coin_amount($report[$group]['order_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['order_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['share_amount'] ? formatted_coin_amount($report[$group]['share_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['share_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['profit'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.orders') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">手續費統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>訂單手續費</th>
                            <th>訂單手續費價值</th>
                            <th>提現手續費</th>
                            <th>提現手續費價值</th>
                            <th>錢包手續費</th>
                            <th>錢包手續費價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fee_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['order_fee'] ? formatted_coin_amount($report[$group]['order_fee']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['order_fee_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['withdrawal_fee'] ? formatted_coin_amount($report[$group]['withdrawal_fee']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['withdrawal_fee_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['wallet_fee'] ? formatted_coin_amount($report[$group]['wallet_fee']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['wallet_fee_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.fees') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">手續費分潤統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>分潤數量</th>
                            <th>分潤數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fee_share_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['share_amount'] ? formatted_coin_amount($report[$group]['share_amount'], $coin).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['share_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.fee-shares') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">提現充值統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>提現筆數</th>
                            <th>提現數量</th>
                            <th>提現數量價值</th>
                            <th>充值筆數</th>
                            <th>充值數量</th>
                            <th>充值數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($withdrawal_deposit_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['withdrawal_count'] }}</td>
                                <td>{{ $report[$group]['withdrawal_amount'] ? formatted_coin_amount($report[$group]['withdrawal_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['withdrawal_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['deposit_count'] }}</td>
                                <td>{{ $report[$group]['deposit_amount'] ? formatted_coin_amount($report[$group]['deposit_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['deposit_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.withdrawals-deposits') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">廣告統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>廣告筆數</th>
                            <th>買廣告筆數</th>
                            <th>買廣告數量</th>
                            <th>買廣告數量價值</th>
                            <th>賣廣告筆數</th>
                            <th>賣廣告數量</th>
                            <th>賣廣告數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ad_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['ad_count'] }}</td>
                                <td>{{ $report[$group]['buy_ad_count'] }}</td>
                                <td>{{ $report[$group]['buy_ad_amount'] ? formatted_coin_amount($report[$group]['buy_ad_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['buy_ad_price'].' '.$base_currency }}</td>
                                <td>{{ $report[$group]['sell_ad_count'] }}</td>
                                <td>{{ $report[$group]['sell_ad_amount'] ? formatted_coin_amount($report[$group]['sell_ad_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['sell_ad_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.ads') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h2 class="card-title">劃轉統計</h2></div>
            <div class="card-block">
                @foreach ($groups as $group)
                <div class="card-block__title">{{ $group }}</div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>市場價格</th>
                            <th>劃轉筆數</th>
                            <th>劃轉數量</th>
                            <th>劃轉數量價值</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfer_report as $coin => $report)
                            @if(!empty($report[$group]))
                            <tr>
                                <td>{{ $coin }}</td>
                                <td>{{ $report[$group]['exchange_rate'] }}</td>
                                <td>{{ $report[$group]['transfer_count'] }}</td>
                                <td>{{ $report[$group]['transfer_amount'] ? formatted_coin_amount($report[$group]['transfer_amount']).' '.$coin : null }}</td>
                                <td>{{ $report[$group]['transfer_price'].' '.$base_currency }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                <a href="{{ route('admin.report.transfers') }}">歷史記錄</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script>
$(function () {
    $('#search-submit').on('click', function (e) {
        var date = $('[name="date"]').val();
        var url = '{{ route('admin.report.index') }}/' + date;
        window.location.href = url;
    });

});
</script>
@endpush
