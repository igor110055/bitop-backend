<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- Vendor styles -->
        <link rel="stylesheet" href="/vendors/bower_components/material-design-iconic-font/dist/css/material-design-iconic-font.min.css">
        <link rel="stylesheet" href="/vendors/bower_components/animate.css/animate.min.css">
        <link rel="stylesheet" href="/vendors/bower_components/jquery.scrollbar/jquery.scrollbar.css">
        <link rel="stylesheet" href="/vendors/bower_components/fullcalendar/dist/fullcalendar.min.css">
        <link rel="stylesheet" href="/vendors/bower_components/select2/dist/css/select2.min.css">
        <link rel="stylesheet" href="/vendors/bower_components/dropzone/dist/dropzone.css">
        <link rel="stylesheet" href="/vendors/bower_components/flatpickr/dist/flatpickr.min.css" />
        <link rel="stylesheet" href="/vendors/bower_components/nouislider/distribute/nouislider.min.css">
        <link rel="stylesheet" href="/vendors/bower_components/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css">
        <link rel="stylesheet" href="/vendors/bower_components/trumbowyg/dist/ui/trumbowyg.min.css">

        <!-- App styles -->
        @stack('styles')
        <link rel="stylesheet" href="/css/app.min.css">

        <title>@yield('title', config('app.name'))</title>
    </head>
    <body data-ma-theme="green">
        @yield('main')

        <!-- Vendors -->
        <script src="/vendors/bower_components/jquery/dist/jquery.min.js"></script>
        <script src="/vendors/bower_components/tether/dist/js/tether.min.js"></script>
        <script src="/vendors/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="/vendors/bower_components/Waves/dist/waves.min.js"></script>
        <script src="/vendors/bower_components/jquery.scrollbar/jquery.scrollbar.min.js"></script>
        <script src="/vendors/bower_components/jquery-scrollLock/jquery-scrollLock.min.js"></script>
        <script src="/vendors/bower_components/Waves/dist/waves.min.js"></script>
        <script src="/vendors/bower_components/remarkable-bootstrap-notify/dist/bootstrap-notify.min.js"></script>

        <script src="/vendors/bower_components/salvattore/dist/salvattore.min.js"></script>
        <script src="/vendors/bower_components/jquery-mask-plugin/dist/jquery.mask.min.js"></script>
        <script src="/vendors/bower_components/select2/dist/js/select2.full.min.js"></script>
        <script src="/vendors/bower_components/dropzone/dist/min/dropzone.min.js"></script>
        <script src="/vendors/bower_components/moment/min/moment.min.js"></script>
        <script src="/vendors/bower_components/flatpickr/dist/flatpickr.min.js"></script>
        <script src="/vendors/bower_components/nouislider/distribute/nouislider.min.js"></script>
        <script src="/vendors/bower_components/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js"></script>
        <script src="/vendors/bower_components/trumbowyg/dist/trumbowyg.min.js"></script>

        <!-- App functions and actions -->
        <script src="/js/app.min.js"></script>

        @stack('scripts')
    </body>
</html>

