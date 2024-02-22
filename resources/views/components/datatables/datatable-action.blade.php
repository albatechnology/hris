<div class="btn-group">
    @if($showRoute && auth()->user()->type->hasPermission($access, 'read'))
    <a class="btn btn-sm btn-primary" href="{{ $showRoute }}" target="_blank">
        View
    </a>
    @endif

    @if($editRoute && auth()->user()->type->hasPermission($access, 'update'))
    <a class="btn btn-sm btn-warning" href="{{ $editRoute }}" target="_blank">
        Edit
    </a>
    @endif

    @if($destroyRoute && auth()->user()->type->hasPermission($access, 'delete'))
    <button class="btn btn-sm btn-danger" onclick="if(confirm('This action cannot be undone. Are you sure you want to delete the selected data?')){ return this.querySelector('form').submit(); };">
        Delete

        <form action="{{ $destroyRoute }}" method="POST" style="display: none;" class="d-none">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
    </button>


    @endif
</div>