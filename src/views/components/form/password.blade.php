<label class="font-semibold text-sm text-gray-600 dark:text-white pb-1 block">{{ $label ?? 'undefine' }}
    <i class="fas fa-eye" onclick="$inputs.passwordToogle('{{ $id ?? $name }}')"></i>
</label>
<input type="password" name="{{ $name ?? 'name' }}" id="{{ $id ?? $name }}"
    {{ $required ?? '' == "true" ? "required" : "" }} value="{{ $value ?? old($name) }}"
    placeholder="{{ $placeholder ?? '' }}"
    class="border rounded-lg focus:border-black dark:focus:border-white dark:bg-gray-900 dark:text-white px-3 py-3 mt-1 mb-5 text-sm w-full" />
@error($name)
<span class="invalid-feedback text-black dark:text-white" role="alert">
    <strong>{{ $message }}</strong>
</span>
@enderror
