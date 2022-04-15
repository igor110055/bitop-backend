@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">商户管理</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        @can('edit-merchants')
        <a
            href="{{ route('admin.merchants.create') }}"
            class="btn btn-primary waves-effect"
        ><i class="zmdi zmdi-plus"></i> 新增商户</a>
        @endcan
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="merchants" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Id</th>
                        <th>商户名称</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($merchants as $merchant)
                    <tr>
                        <td><a href="{{ route('admin.merchants.show', ['merchant' => $merchant->id]) }}">{{ $merchant->id }}</a></td>
                        <td><a href="{{ route('admin.merchants.show', ['merchant' => $merchant->id]) }}">{{ $merchant->name }}</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendors/bower_components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="/vendors/bower_components/jszip/dist/jszip.min.js"></script>
<script src="/vendors/bower_components/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script>
$(function(){
    var table = $('#merchants').DataTable();
});
</script>
@endpush

