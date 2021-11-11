@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">組織管理：{{ $page_title }}</h2>
        <!--small class="card-subtitle"></small-->
    </div>
    <div class="card-block">
        <a
            href="{{ route('admin.agencies.edit', ['agency' => $agency->id]) }}"
            class="btn btn-primary waves-effect"
        >編輯資料</a>
        <a
            href="{{ route('admin.agencies.agents', ['agency' => $agency->id]) }}"
            class="btn btn-primary waves-effect"
        >業務管理</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">基本資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">ID</dt>
                    <dd class="col-sm-9">{{ $agency->id }}</dd>
                    <dt class="col-sm-3">名稱</dt>
                    <dd class="col-sm-9">{{ $agency->name }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">法幣資產</h3></div>
            <div class="card-block">
                <table id="assets" class="table">
                    <thead>
                        <tr>
                            <th>幣別</th>
                            <th>餘額</th>
                            <th>單價</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assets as $asset)
                        <tr>
                            <td><a href="{{ route('admin.assets.show', ['asset' => $asset->id]) }}">{{ $asset->currency }}</a></td>
                            <td>{{ $asset->balance }}</td>
                            <td>{{ $asset->unit_price }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
