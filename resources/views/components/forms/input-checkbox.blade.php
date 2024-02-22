<div class="form-group {{ $class }}">
    <label for="{{ $name }}" class="d-flex justify-content-between">{{ $label }}
    </label>
    <input type="checkbox" name="{{ $name }}" class="bootstrap-switch {{ $errors->has($name) ? 'is-invalid' : null }} {{ $name }}" aria-describedby="{{ $name }}-error" id="{{ $name }}" data-on-text="{{ $onText }}" data-off-text="{{ $offText }}" {{ $value == true ? 'checked' : null }} value="1">

    @if($errors->has($name))
    <span id="{{ $name }}-error" class="error invalid-feedback">{{ $errors->first($name) }}</span>
    @endif

    <div class="text-xs text-gray-600 mt-2">{{ $helper }}</div>
</div>
