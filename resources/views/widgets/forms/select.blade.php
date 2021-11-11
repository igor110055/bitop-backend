<div class="form-group">
    <label>{{ $title }}</label>
    <select
        class="form-control select2 {{ $class ?? '' }}"
        name="{{ $name }}"
        {{ isset($disabled) && $disabled ? 'disabled' : ''}}
        {{ isset($required) && $required ? 'required' : '' }}
    >
        @foreach($values as $v => $t)
        <option
            value="{{ $v }}"
            {{ $value === $v ? 'selected' : ''}}
        >{{ $t }}</option>
        @endforeach
    </select>
</div>
