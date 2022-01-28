<div class="form-group">
    <div class="form-group mb-0">
        <label {!! isset($id) ? "for='$id'" : '' !!}>{{ $title }}</label>
        <input
            {!! isset($id) ? 'id="'.$id.'"' : '' !!}
            type="{{ $type ?? 'text' }}"
            class="form-control {{ $class ?? '' }}"
            name="{{ $name }}"
            value="{{ $value ?? '' }}"
            {!! isset($mask) ? "data-mask='$mask'" : '' !!}
            {!! isset($placeholder) ? "placeholder='$placeholder'" : '' !!}
            {{ isset($disabled) && $disabled ? 'disabled' : '' }}
            {{ isset($required) && $required ? 'required' : '' }}
            {!! isset($attributes) ? $attributes : '' !!}
        >
        <i class="form-group__bar"></i>
    </div>
    @isset($help)
    <small>{{ $help }}</small>
    @endisset
</div>
