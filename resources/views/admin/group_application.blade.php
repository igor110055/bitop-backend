@php
    use App\Models\GroupApplication;
@endphp

@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<div class="card">
    <div class="card-header">
        <h2 class="card-title">{{ $application->group_name }} 群組申請</h2>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">申請資料</h3></div>
            <div class="card-block">
                <dl class="row">
                    <dt class="col-sm-3">Id</dt>
                    <dd class="col-sm-9">{{ $application->id }}</dd>
                    <dt class="col-sm-3">申請人Id</dt>
                    <dd class="col-sm-9">{{ $application->user_id }}</dd>
                    <dt class="col-sm-3">申請人</dt>
                    <dd class="col-sm-9">{{ $application->user->username }}</dd>
                    <dt class="col-sm-3">群組名稱</dt>
                    <dd class="col-sm-9">{{ $application->group_name }}</dd>
                    <dt class="col-sm-3">狀態</dt>
                    <dd class="col-sm-9">
                        @if ($application->status === GroupApplication::STATUS_PROCESSING)
                            <span class="badge badge-pill badge-warning">{{ $application->status }}</span>
                        @elseif ($application->status === GroupApplication::STATUS_PASS)
                            <span class="badge badge-pill badge-success">{{ $application->status }}</span>
                        @elseif ($application->status === GroupApplication::STATUS_REJECT)
                            <span class="badge badge-pill badge-danger">{{ $application->status }}</span>
                        @endif
                    </dd>
                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $application->description }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if ($application->status === GroupApplication::STATUS_PROCESSING)
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">驗證</h3></div>
                <div class="card-block">
                <form action="{{ route('admin.groups.application-verify', ['application' => $application->id]) }}" method="post">
                {{ csrf_field() }}
                    @include('widgets.forms.input', ['name' => 'description', 'value' => '', 'title' => 'Description', 'required' => true])
                    <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                    <button type="submit" name="action" value="pass" class="btn btn-primary">Pass</button>
                </form>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
