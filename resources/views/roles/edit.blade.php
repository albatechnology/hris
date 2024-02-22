@extends('layouts.base')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Edit Role</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="{{ route('roles.update', $model) }}" method="post" class="form-loading">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Group</label>
                        <select name="group_id" id="group_id"
                            class="form-control select2 @error('group_id') is-invalid @enderror">
                            @foreach ($groups as $id => $name)
                                <option value="{{ $id }}" @if ($model->group_id == $id) selected @endif>
                                    {{ $name }}</option>
                            @endforeach
                        </select>
                        @error('group_id')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="required">Nama</label>
                        <input name="name" type="text" value="{{ $model->name }}"
                            class="form-control @error('name') is-invalid @enderror" placeholder="Name" required>
                        @error('name')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="required">Hak Akses</label>
                        <div class="row">
                            @foreach ($permissions as $permission1)
                                <div class="col-md-4">
                                    @if ($permission1->childs->count() > 0)
                                        <ul style="list-style-type: none; padding: 0;">
                                            <li>
                                                <div class="custom-control custom-checkbox">
                                                    <input name="permission_ids[]" class="custom-control-input"
                                                        type="checkbox" id="permission-{{ $permission1->id }}"
                                                        value="{{ $permission1->id }}"
                                                        @if (in_array($permission1->id, $rolePermissions)) checked @endif>
                                                    <label for="permission-{{ $permission1->id }}"
                                                        class="custom-control-label">{{ str_replace('_', ' ', $permission1->name) }}</label>
                                                </div>
                                                <ul style="list-style-type: none;">
                                                    @foreach ($permission1->childs as $permission2)
                                                        <li>
                                                            <div class="custom-control custom-checkbox">
                                                                <input name="permission_ids[]" class="custom-control-input"
                                                                    type="checkbox" id="permission-{{ $permission2->id }}"
                                                                    value="{{ $permission2->id }}"
                                                                    @if (in_array($permission2->id, $rolePermissions)) checked @endif>
                                                                <label for="permission-{{ $permission2->id }}"
                                                                    class="custom-control-label">{{ str_replace('_', ' ', $permission2->name) }}</label>
                                                            </div>

                                                            @if ($permission2->childs->count() > 0)
                                                                <ul style="list-style-type: none;">
                                                                    @foreach ($permission2->childs as $permission3)
                                                                        <li>
                                                                            <div class="custom-control custom-checkbox">
                                                                                <input name="permission_ids[]"
                                                                                    class="custom-control-input"
                                                                                    type="checkbox"
                                                                                    id="permission-{{ $permission3->id }}"
                                                                                    value="{{ $permission3->id }}"
                                                                                    @if (in_array($permission3->id, $rolePermissions)) checked @endif>
                                                                                <label
                                                                                    for="permission-{{ $permission3->id }}"
                                                                                    class="custom-control-label">{{ str_replace('_', ' ', $permission3->name) }}</label>
                                                                            </div>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                        </ul>
                                    @else
                                        <ul style="list-style-type: none; padding: 0;">
                                            <li>
                                                <div class="custom-control custom-checkbox">
                                                    <input name="permission_ids[]" class="custom-control-input"
                                                        type="checkbox" id="permission-{{ $permission1->id }}"
                                                        value="{{ $permission1->id }}"
                                                        @if (in_array($permission1->id, $rolePermissions)) checked @endif>
                                                    <label for="permission-{{ $permission1->id }}"
                                                        class="custom-control-label">{{ str_replace('_', ' ', $permission1->name) }}</label>
                                                </div>
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('permissions')
                            <span class="error invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="pb-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </section>
@endsection
