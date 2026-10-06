<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    @include('partials.head')
</head>
<body class=" ">
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body">
                <img src="{{ asset('assets/images/loader.webp') }}" alt="loader" class="image-loader img-fluid" />
            </div>
        </div>
    </div>

    <div class="wrapper">
        {{ $slot }}
    </div>

    {{-- Scripts --}}
    <script src="{{ asset('assets/js/core/libs.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/slider-tabs.js') }}"></script>
    <script src="{{ asset('assets/vendor/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('assets/js/iqonic-script/utility.min.js') }}"></script>
    <script src="{{ asset('assets/js/iqonic-script/setting.min.js') }}"></script>
    <script src="{{ asset('assets/js/setting-init.js') }}"></script>
    <script src="{{ asset('assets/js/core/external.min.js') }}"></script>
    <!-- qompacui Script -->
    <script src="{{ asset('assets/js/qompac-ui.js') }}?v=2.0.0" defer></script>
    <script src="{{ asset('assets/js/sidebar.js') }}?v=2.0.0" defer></script>

</body>
</html>
