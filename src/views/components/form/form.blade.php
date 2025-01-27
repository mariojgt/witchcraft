<form action="{{ $action ?? '/' }}" method="{{ $method ?? 'POST' }}"
    {{ $file ?? '' == "true" ? 'enctype="multipart/form-data"' : "" }}>
    @csrf

    {{ $slot }}
</form>
