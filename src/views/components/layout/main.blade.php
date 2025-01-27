<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title ?? 'Witchcraft Media Manager' }}</title>
    @vite('resources/vendor/Witchcraft/js/vue.js', 'vendor/Witchcraft')
    @vite('resources/vendor/Witchcraft/sass/app.scss', 'vendor/Witchcraft')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @stack('css')
</head>

<body>
    <div id="app" >
        <x-witchcraft::layout.flash />
        {{ $slot }}
    </div>
    @stack('js')
</body>

</html>
