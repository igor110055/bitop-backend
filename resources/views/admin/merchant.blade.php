@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">商户管理：{{ $merchant->id }}({{ $merchant->name }})</h2>
        <!--small class="card-subtitle"></small-->
    </div>
    <div class="card-block">
        @can('edit-merchants')
        <form method="post" action="{{ route('admin.merchants.destroy', ['merchant' => $merchant->id]) }}">
            {{ csrf_field() }}
            {{ method_field('DELETE') }}
            <button type="submit" class="btn btn-danger">删除商户</button>
        </form>
        @endcan
    </div>
</div>

<form method="post" action="{{ route('admin.merchants.update', ['merchant' => $merchant->id]) }}">
{{ csrf_field() }}
{{ method_field('PUT') }}
    <div class="card">
        <div class="card-header"><h3 class="card-title">商户资讯</h3></div>
        <div class="card-block">
            @include('widgets.forms.input', ['name' => 'id', 'class' => 'text-lowercase', 'value' => old('id', $merchant->id), 'title' => 'ID', 'disabled' => true, 'required' => true, 'placeholder' => '请输入小写英文、数字、dash、底线，至少 6 个字元'])
            @include('widgets.forms.input', ['name' => 'name', 'value' => old('name', $merchant->name), 'title' => 'Name', 'required' => true, 'help' => '仅作为辨识用'])
            @can('edit-merchants')
            <button type="submit" class="btn btn-primary">储存</button>
            @endcan
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><h3 class="card-title">汇率设定</h3></div>
    <div class="card-block">
        <p>
            全币别汇率取得 API endpoint： {{ url("api/merchants/$merchant->id/exchange-rates") }}<br>
            单一币别汇率取得 API endpoint： {{ url("api/merchants/$merchant->id/exchange-rates/<coin>") }}
        </p>
        <div class="table-responsive">
            <table id="merchants" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>coin</th>
                        <th>目前设定</th>
                        <th>买入</th>
                        <th>卖出</th>
                        @can('edit-merchants')
                        <th>设定</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach($exchange_rates as $rate)
                    <tr>
                        <td>{{ $rate['coin'] }}</td>
                        <td>{{ __("messages.merchant.exchange_rate.".$rate['type']) }}</td>
                        <td>{{ $rate['bid'] }}</td>
                        <td>{{ $rate['ask'] }}</td>
                        @can('edit-merchants')
                        <td><a href="{{ route('admin.merchants.exchange-rate', ['merchant' => $merchant->id, 'coin' => $rate['coin']]) }}">设定汇率</a></td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
</script>
@endpush

