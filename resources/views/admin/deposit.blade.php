@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">充值Id：{{ $deposit->id }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">充值細節</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">Transaction</dt>
                    <dd class="col-sm-9">{{ $deposit->transaction }}</dd>
                    <dt class="col-sm-3">Address</dt>
                    <dd class="col-sm-9">{{ $deposit->address }}</dd>
                    <dt class="col-sm-3">Tag</dt>
                    <dd class="col-sm-9">{{ $deposit->tag }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@endpush
