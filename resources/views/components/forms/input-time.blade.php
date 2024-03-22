<div class="form-group {{ $class }}">
    <label for="{{ $name }}" class="d-flex justify-content-between">{{ $label }}
        <span class="text-xs text-secondary float-right">{{ $required }}</span>
    </label>
    <input type="datetime-local" name="{{ $name }}" class="form-control {{ $errors->has($name) ? 'is-invalid' : null }} {{ $name }}" aria-describedby="{{ $name }}-error" id="{{ $name }}" placeholder="{{ $placeholder }}" minlength="{{ $minlength }}" {{ $required ? 'required' : null }} value="{{ old($name, $value) }}">

    @if($errors->has($name))
    <span id="{{ $name }}-error" class="error invalid-feedback">{{ $errors->first($name) }}</span>
    @endif

    <div class="text-xs text-gray-600 mt-2">{{ $helper }}</div>
</div>