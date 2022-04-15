@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ route('admin.merchants.create-exchange-rate', ['merchant' => $merchant->id, 'coin' => $coin]) }}">
    {{ csrf_field() }}
    <div class="card">
        <div class="card-header"><h2 class="card-title">{{ $merchant['id'] }} {{ $coin }} 汇率设定</h2></div>
        <div class="card-block">
            <div>
                <h5>即时汇率 <span class="ml-4">买入 {{ $system_exchange_rate[0]}}</span><span class="ml-4">卖出 {{ $system_exchange_rate[1]}}</span></h5>
                <p>
                    此币别汇率取得 API endpoint： {{ url("api/merchants/$merchant->id/exchange-rates/{$coin}") }}
                </p>
            </div>
            <div class="mt-3 ml-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" value="system" id="type-system" {{ ($exchange_rate['type'] === 'system') ? 'checked' : '' }} >
                    <label class="form-check-label" for="type-system">
                        {{ __("messages.merchant.exchange_rate.system") }} (system)
                    </label>
                </div>
            </div>
            <div class="mt-3 ml-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" value="fixed" id="type-fixed" {{ ($exchange_rate['type'] === 'fixed') ? 'checked' : '' }} >
                    <label class="form-check-label" for="type-fixed">
                        {{ __("messages.merchant.exchange_rate.fixed") }} (fixed)
                    </label>
                </div>
                <div class="m-3">
                    @include('widgets.forms.input', ['name' => 'bid', 'class' => '', 'value' => data_get($exchange_rate, 'exchange_rate.bid', ''), 'title' => '人工汇率买入'])
                    @include('widgets.forms.input', ['name' => 'ask', 'class' => '', 'value' => data_get($exchange_rate, 'exchange_rate.ask', ''), 'title' => '人工汇率卖出'])
                </div>
            </div>
            <div class="mt-3 ml-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" value="floating" id="type-floating" {{ ($exchange_rate['type'] === 'floating') ? 'checked' : '' }} >
                    <label class="form-check-label" for="type-floating">
                        {{ __("messages.merchant.exchange_rate.floating") }} (floating)
                    </label>
                </div>
                <div class="m-3">
                    @include('widgets.forms.input', ['name' => 'bid_diff', 'class' => '', 'value' => data_get($exchange_rate, 'exchange_rate.bid_diff', ''), 'title' => '调整买入汇率'])
                    @include('widgets.forms.input', ['name' => 'ask_diff', 'class' => '', 'value' => data_get($exchange_rate, 'exchange_rate.ask_diff', ''), 'title' => '调整卖出汇率'])
                </div>
            </div>
            <div class="mt-3 ml-3">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" value="diff" id="type-diff" {{ ($exchange_rate['type'] === 'diff') ? 'checked' : '' }} >
                    <label class="form-check-label" for="type-diff">
                        {{ __("messages.merchant.exchange_rate.diff") }} (diff)
                    </label>
                </div>
                <div class="m-3">
                    @include('widgets.forms.input', ['name' => 'diff', 'class' => '', 'value' => data_get($exchange_rate, 'exchange_rate.diff', ''), 'title' => '买卖汇差'])
                </div>
            </div>
            <button type="submit" class="btn btn-primary">储存</button>
        </div>
    </div>
</form>
@endsection
@push('scripts')
<script>
</script>
@endpush


