@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ $route }}">
{{ csrf_field() }}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">組織管理：{{ $page_title }}</h2>
            <!--small class="card-subtitle"></small-->
        </div>
    </div>
    <div class="card">
        <div class="card-block">
            @if($action === 'create')
                @include('widgets.forms.input', ['name' => 'id', 'class' => 'text-lowercase', 'value' => old('id', $agency->id), 'title' => 'ID', 'required' => true, 'placeholder' => '請輸入小寫英文、數字、dash、底線，至少 6 個字元'])
            @else
                @method('PUT')
            @endif
            @include('widgets.forms.input', ['name' => 'name', 'value' => old('name', $agency->name), 'title' => 'Name', 'required' => true])
            <a href="{{ route('admin.agencies.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection