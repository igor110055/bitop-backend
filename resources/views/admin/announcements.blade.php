@php
    use App\Models\Announcement;
    use Illuminate\Support\Str;
@endphp
@push('styles')
    <link rel="stylesheet" href="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.css">
@endpush

@extends('layouts.main')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">公告管理</h2>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">公告發佈</h2>
        <small class="card-subtitle" style="color:#dc1818">請先發佈英文版</small>
    </div>

    <div class="card-block">
        <div class="row">
            <div class="col-sm-3">
                <div>
                    <label>Release time</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                        <div class="form-group">
                            <input id="release_time" type="text" class="form-control datetime-picker flatpickr-input active" placeholder="Pick a date &amp; time" readonly="readonly">
                            <i class="form-group__bar"></i>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 20%;">
                    <label>Stay on PIN time</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                        <div class="form-group">
                            <input id="pin_time" type="text" class="form-control datetime-picker flatpickr-input active" placeholder="Pick a date &amp; time" readonly="readonly">
                            <i class="form-group__bar"></i>
                        </div>
                    </div>
                </div>
                <div>
                <button class="btn btn-primary mt-4" id="submit" name="submit" value="1">Submit</button>
                </div>
            </div>

            <div class="col-sm-9">
                <div>
                    <label>Announcement Title</label>
                    <input type="text" id="title" class="form-control" autocomplete="off">
                </div>
                <div class="row">
                    <div class="card-block">
                        <div id="editor"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-block">
        <div class="table-responsive">
            <table id="announcements" class="table table-striped">
                <thead class="thead-default">
                    <tr>
                        <th>Release time</th>
                        <th>Pin time</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Cancel</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($announcements as $announcement)
                    <tr>
                        <td>
                            {{ datetime($announcement->released_at) }}
                        </td>
                        <td>
                        @if ($announcement->pin_end_at)
                            {{ datetime($announcement->pin_end_at) }}
                        @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.announcements.show', ['announcement' => $announcement]) }}">
                            "{{ Str::limit($announcement->title, 20) }}"
                            </a>
                        </td>
                        <td>
                        @if ($announcement->status === Announcement::STATUS_PENDING)
                            <span class="badge badge-pill badge-default }}">Pending</span>
                        @elseif ($announcement->status === Announcement::STATUS_ANNOUNCED)
                            <span class="badge badge-pill badge-success }}">Announced</span>
                        @elseif ($announcement->status === Announcement::STATUS_CANCELED)
                            <span class="badge badge-pill badge-danger }}">Canceled</span>
                        @endif
                        </td>
                        <td>
                        @if ($announcement->deleted_at === null)
                            <button class="btn btn-primary wave-effect cancel" id="{{ $announcement->id }}">Cancel</button>
                        @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/18.0.0/classic/ckeditor.js"></script>
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function(){
    let myEditor;
    ClassicEditor
        .create( document.querySelector( '#editor' ) )
        .then(editor => {
            myEditor = editor;
        })
        .catch(error => {
            console.error(error);
        });

    $('#submit').on('click', function (e) {
        var text = myEditor.getData();
        var release_time = $('#release_time').val() ?? null;
        var pin_time = $('#pin_time').val() ?? null;
        var title = $('#title').val();

        if (title == '') {
            $.notify({ message: '請輸入公告標題.' }, { type: 'danger' });
            return;
        }

        if (text == '') {
            $.notify({ message: '請輸入公告內容.' }, { type: 'danger' });
            return;
        }

        $.ajax({
            type: 'POST',
            url: "{{ route('admin.announcements.store') }}",
            data: {
                release_time: release_time,
                pin_time: pin_time,
                title: title,
                text: text,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
        }).done(function () {
            window.location.reload();
            $.notify({ message: 'Create successfully.' }, { type: 'inverse' });
        }).fail(function (err) {
            $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
        })
    });

    $('#announcements').on('click', '.cancel', function (e) {
        e.preventDefault();
        var button = $(this);
        swal({
            title: '確認執行該操作?',
            text: '取消此公吿',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'PUT',
                    url: '/admin/announcements/' + button.attr("id") + '/cancel',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                }).done(function () {
                    $.notify({ message: 'Cancelled successfully.' }, { type: 'inverse' });
                    window.location.reload();
                }).fail(function (err) {
                    $.notify({ message: 'The announcement has been sent or cancelled. Please refresh the page.' }, { type: 'danger' });
                })
            }
        }).catch(swal.noop);
    });
});
</script>
@endpush
