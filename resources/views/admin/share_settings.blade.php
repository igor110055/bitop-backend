@push('styles')
<link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">群組 <a href="{{ route('admin.groups.show', ['group' => $group->id]) }}">{{ $group->name }}</a> 分帳設定</h2>
        <!--small class="card-subtitle"></small-->
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="share-settings" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>分帳對象</th>
                        <th>百分比（%）</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($share_settings as $setting)
                    <tr>
                        <td><a href="{{ route('admin.users.show', ['user' => $setting->user->id]) }}">{{ $setting->user->name.' '.$setting->user->email }}</a></td>
                        <td>{{ $setting->percentage }}</td>
                        <td>
                            <a href="#" class="share-setting-delete" data-id="{{ $setting->id }}"><i class="zmdi zmdi-close"></i> 刪除</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <a href="{{ route('admin.groups.share-settings.create', ['group' => $group->id]) }}" class="btn btn-primary btn-block"><i class="zmdi zmdi-plus"></i> 新增群組分帳</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function(){
    $("#share-settings").on('click', '.share-setting-delete', function (event) {
        event.preventDefault();
        var e = $(this);
        var _token = "{{ csrf_token() }}";
        swal({
            title: 'Are you sure?',
            text: '這將會刪除此分帳設定',
            type: 'warning',
            showCancelButton: true,
            buttonsStyling: false,
            confirmButtonClass: 'btn btn-danger',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonClass: 'btn btn-secondary'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: '{{ route('admin.groups.share-settings.destroy', ['group' => $group->id]) }}',
                    method: 'DELETE',
                    data: { share_setting_id: e.data('id'), _token: _token },
                    dataType: 'json',
                }).done(function (data) {
                    e.closest('tr').remove();
                    $.notify({ message: '設定已刪除' }, { type: 'inverse' });
                }).fail(function (err) {
                    $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
                })
            }
        }).catch(swal.noop);
    });
});
</script>
@endpush
