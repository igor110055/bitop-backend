@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">提現Id：{{ $withdrawal->id }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">提現細節</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">Transaction</dt>
                    <dd class="col-sm-9">{{ $withdrawal->transaction }}</dd>
                    <dt class="col-sm-3">Address</dt>
                    <dd class="col-sm-9">{{ $withdrawal->address }}</dd>
                    <dt class="col-sm-3">Tag</dt>
                    <dd class="col-sm-9">{{ $withdrawal->tag }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
@endpush
