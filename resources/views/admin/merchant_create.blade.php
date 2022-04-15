@extends('layouts.main')

@section('content')
@include('widgets.error_messages', ['errors' => $errors])
<form method="post" action="{{ route('admin.merchants.store') }}">
{{ csrf_field() }}
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">商户管理：{{ $page_title }}</h2>
            <!--small class="card-subtitle"></small-->
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">商户资讯</h3></div>
        <div class="card-block">
            @include('widgets.forms.input', ['name' => 'id', 'class' => 'text-lowercase', 'value' => old('id', $merchant->id), 'title' => 'ID', 'required' => true, 'placeholder' => '请输入小写英文、数字、dash、底线，至少 6 个字元', 'help' => '商户汇率 API URL 将会依据 ID 产生' ])
            @include('widgets.forms.input', ['name' => 'name', 'value' => old('name', $merchant->name), 'title' => 'Name', 'required' => true, 'help' => '仅作为辨识用'])
            <a href="/admin/merchants" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
</script>
@endpush

