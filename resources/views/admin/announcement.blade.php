@php
    use App\Models\Announcement;
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
        <h2 class="card-title">編輯公告</h2>
    </div>

    <div class="card-block">
        <div class="row">
            <div class="col-sm-3">
                <div>
                    <label>Release time</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                        <div class="form-group">
                            <input id="release_time" type="text" class="form-control datetime-picker flatpickr-input active" placeholder="Pick a date &amp; time" readonly="readonly" value="{{ datetime($announcement->released_at) }}">
                            <i class="form-group__bar"></i>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 20%;">
                    <label>Stay on PIN time</label>
                    <div class="input-group">
                        <span class="input-group-addon"><i class="zmdi zmdi-calendar"></i></span>
                        <div class="form-group">
                            <input id="pin_time" type="text" class="form-control datetime-picker flatpickr-input active" placeholder="Pick a date &amp; time" readonly="readonly" value="{{ $announcement->pin_end_at ? datetime($announcement->pin_end_at) : null }}">
                            <i class="form-group__bar"></i>
                        </div>
                    </div>
                </div>
                <div>
                <button class="btn btn-primary mt-4" id="submit" name="submit" value="1">Submit</button>
                </div>
                <div>
                <button class="btn btn-danger mt-5" id="broadcast" name="broadcast" value="1">Broadcast</button>
                </div>
            </div>

            <div class="col-sm-9">
                <div class="col-sm-3" style="padding:unset">
                    @include('widgets.forms.select', ['name' => 'locale', 'class' => 'search-control', 'title' => 'Select locale', 'value' => $locale['en'], 'values' => $locale])
                </div>
                <div>
                    <label>Announcement Title</label>
                    <input type="text" id="title" class="form-control" autocomplete="off" value="{{ $announcement->title }}">
                </div>
                <div class="row">
                    <div class="card-block">
                        <div id="editor"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/18.0.0/classic/ckeditor.js"></script>
<script src="/vendors/bower_components/sweetalert2/dist/sweetalert2.min.js"></script>
<script>
$(function(){
    let contents = @json($contents);
    let myEditor;
    ClassicEditor
        .create( document.querySelector( '#editor' ) )
        .then(editor => {
            myEditor = editor;
            editor.setData(contents.en.content);
        })
        .catch(error => {
            console.error(error);
        });

    $('select').on('change', function (e) {
        let locale = $('[name="locale"]').val();
        if (locale in contents) {
            $('#title').prop('value', contents[locale].title);
            myEditor.setData(contents[locale].content);
        } else {
            $('#title').prop('value', '');
            myEditor.setData('');
        }
    });

    $('#submit').on('click', function (e) {
        var text = myEditor.getData();
        var release_time = $('#release_time').val() ?? null;
        var pin_time = $('#pin_time').val() ?? null;
        var title = $('#title').val();
        var locale = $('[name="locale"]').val();

        if (title == '') {
            $.notify({ message: '請輸入公告標題.' }, { type: 'danger' });
            return;
        }

        if (text == '') {
            $.notify({ message: '請輸入公告內容.' }, { type: 'danger' });
            return;
        }

        $.ajax({
            type: 'PUT',
            url: "/admin/announcements/{{ $announcement->id }}",
            data: {
                release_time: release_time,
                pin_time: pin_time,
                title: title,
                text: text,
                locale: locale,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
        }).done(function () {
            window.location.reload();
            $.notify({ message: 'Created successfully.' }, { type: 'inverse' });
        }).fail(function (err) {
            $.notify({ message: 'Oops... there is something wrong!.' }, { type: 'danger' });
        })
    });

    $('#broadcast').on('click', function (e) {
        e.preventDefault();
        var button = $(this);
        swal({
            title: "確認廣播公告 " + "{{ $announcement->title }}",
            text: '取消此公吿廣播',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('admin.announcements.email-broadcast', ['announcement' => $announcement->id]) }}",
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                }).done(function () {
                    $.notify({ message: 'broadcast successfully.' }, { type: 'inverse' });
                }).fail(function (err) {
                    $.notify({ message: 'Broadcast failed. Please refresh the page.' }, { type: 'danger' });
                })
            }
        }).catch(swal.noop);
    });
});
</script>
@endpush
