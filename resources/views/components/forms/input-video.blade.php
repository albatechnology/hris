<div class="border border-gray-200 rounded-md p-5 ct-input-img-section {{ $class }}">
  <label class="flex flex-col sm:flex-row mb-2"> {{ $label }}
    <span class="sm:ml-auto mt-1 sm:mt-0 text-xs text-gray-600">{{ $required }}</span>
  </label>
  <div class="w-40 h-40 relative image-fit cursor-pointer zoom-in mx-auto">
    <img class="rounded-md" alt="image" src="{{ $model->getFirstMediaUrl($name) ? $model->getFirstMediaUrl($name) : asset('img/200x200.jpg') }}">
    <div title="Are you sure want to remove?" class="tooltip w-5 h-5 flex items-center justify-center absolute rounded-full text-white bg-theme-6 right-0 top-0 -mr-2 -mt-2 ct-input-img-remove" style="display: none;"> <i data-feather="x" class="w-4 h-4"></i> </div>
  </div>
  <div class="w-40 mx-auto text-xs text-gray-600">{{ $helper }}</div>
  <div class="w-40 mx-auto cursor-pointer relative mt-5">
    @if($errors->has($name))
      <span id="{{ $name }}-error" class="error invalid-feedback">{{ $errors->first($name) }}</span>
    @endif

    <button type="button" class="btn btn-info w-full ct-input-img-btn">Change Video</button>
    <input type="file" name="{{ $name }}" class="d-none ct-input-img-file {{ $name }}" accept="video/*">
  </div>
</div>