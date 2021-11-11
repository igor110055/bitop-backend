@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">組織管理</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <a
            href="{{ route('admin.agencies.create') }}"
            class="btn btn-primary waves-effect"
        ><i class="zmdi zmdi-plus"></i> 新增組織</a>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="agencies" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>組織名稱</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agencies as $agency)
                    <tr>
                        <td><a href="{{ route('admin.agencies.show', ['agency' => $agency->id]) }}">{{ $agency->id }}</a></td>
                        <td><a href="{{ route('admin.agencies.show', ['agency' => $agency->id]) }}">{{ $agency->name }}</a></td>
                        <td><a href="{{ route('admin.agencies.agents', ['agency' => $agency->id]) }}">業務列表</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('scripts.data_tables')
<script>
$(function(){
    var table = $('#agencies').DataTable();
});
</script>
@endpush
