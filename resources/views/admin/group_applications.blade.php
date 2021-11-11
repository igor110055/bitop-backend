@php
    use App\Models\GroupApplication;
    use Illuminate\Support\Str;
@endphp
@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">申請群組列表</h2>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="applications" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Applier</th>
                        <th>Group Name</th>
                        <th>Status</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $application)
                    <tr>
                        <td><a href="{{ route('admin.users.show', ['user' => $application->user_id]) }}">{{ $application->user_id }}</a></td>
                        <td><a href="{{ route('admin.groups.application', ['application' => $application->id]) }}">{{ $application->group_name }}</a></td>
                        @if ($application->status === GroupApplication::STATUS_PROCESSING)
                        <td><span class="badge badge-pill badge-warning">{{ $application->status }}</span></td>
                        @elseif ($application->status === GroupApplication::STATUS_PASS)
                        <td><span class="badge badge-pill badge-success">{{ $application->status }}</span></td>
                        @elseif ($application->status === GroupApplication::STATUS_REJECT)
                        <td><span class="badge badge-pill badge-danger">{{ $application->status }}</span></td>
                        @endif
                        <td>{{ Str::limit($application->description, 20) }}</td>
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
    var table = $('#applications').DataTable();
});
</script>
@endpush
