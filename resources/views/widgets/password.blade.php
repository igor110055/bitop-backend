<div class="form-group form-group--float">
    <input
        {{ isset($id) ? "id='{$id}'" : '' }}
        type="password"
        class="form-control {{ isset($class) ? $class : '' }}"
        name="{{ name }}"
        {{ isset($disabled) ? 'disabled' : '' }}
    >
    <label>{{ $title }}</label>
    <i class="form-group__bar"></i>
</div>
