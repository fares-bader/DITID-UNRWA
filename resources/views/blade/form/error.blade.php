@props(['name'])
@error($name)
    <span id="{{ $name }}-error" class="alert-msg" role="alert" aria-live="assertive">{{ $message }}</span>
@enderror
