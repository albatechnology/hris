<div class="form-group {{ $class }}">
    <label for="{{ $name }}" class="d-flex justify-content-between">{{ $label }}
        <span class="text-xs text-secondary float-right">{{ $required }}</span>
    </label>
    <select class="select2 {{ $errors->has($name) ? 'is-invalid' : null }} {{ $name }}" aria-describedby="{{ $name }}-error" name="{{ $name }}" data-placeholder="{{ $placeholder }}" {{ $required ? 'required' : 'data-allow-clear' }}>
        <option></option>
        @foreach($options as $option)
        <option value="{{ $option->$optionValue }}" {{ $option->$optionValue == (old($name, $value) ?? request($name)) ? 'selected' : '' }} myTag="{{ $option->$optionLabel }}">{{ $option->$optionLabel }}</option>
        @endforeach
    </select>

    @if($errors->has($name))
    <span id="{{ $name }}-error" class="error invalid-feedback">{{ $errors->first($name) }}</span>
    @endif

    <div class="text-xs text-gray-600 mt-2">{{ $helper }}</div>
</div>