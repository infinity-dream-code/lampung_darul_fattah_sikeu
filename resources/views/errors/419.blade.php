@extends('errors::minimal')

@section('title', __('Memuat ulang'))
@section('code', '419')
@section('message', __('Memuat ulang halaman'))

@section('script')
    <script>
        (function () {
            const key = 'darul_fattah_419_reloaded';
            if (!sessionStorage.getItem(key)) {
                sessionStorage.setItem(key, '1');
                window.location.reload();
                return;
            }
            sessionStorage.removeItem(key);
            const back = document.referrer;
            window.location.href = back && back.indexOf(window.location.origin) === 0
                ? back
                : {{ Illuminate\Support\Js::from(url('/admin')) }};
        })();
    </script>
@endsection
