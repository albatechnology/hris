<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create User</h3>
            </div>

            <div class="card-body">
                <x-forms.select-array name='type' label='Type' placeholder='-- Please Select --' required='Required'
                    :options="\App\Enums\UserType::all()" :$model />
                <x-forms.input-text name='name' label='Name' placeholder='Name' minlength='3'
                    required='Required, at least 3 characters' :$model />
                <x-forms.input-text name='phone' label='Phone' placeholder='Phone' minlength='11'
                    required='Required, at least 11 characters' :$model />
                <x-forms.input-email name='email' label='Email' placeholder='Email' required='Required' :$model />

                @if (!$model->id)
                    <x-forms.input-password name='password' label='Password' placeholder='Password' minlength='8'
                        required='Required, at least 8 characters' :$model />
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @if ($model->id)
            @if ($errors->has('password'))
                <div class="updatePasswordCard">
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Update Password</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                    title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <x-forms.input-password name='password' label='New Password' placeholder='New Password'
                                helper='At least 8 characters' minlength='8' value='' :$model />
                            <x-forms.input-password name='password_confirmation' label='New Password Confirmation'
                                placeholder='New Password Confirmation' minlength='8' value='' :$model />
                        </div>
                    </div>
                </div>
            @else
                <div class="updatePasswordCard">
                    <div class="card card-warning collapsed-card">
                        <div class="card-header">
                            <h3 class="card-title">Update Password</h3>

                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                    title="Collapse">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <x-forms.input-password name='password' label='New Password' placeholder='New Password'
                                helper='At least 8 characters' minlength='8' value='' :$model />
                            <x-forms.input-password name='password_confirmation' label='New Password Confirmation'
                                placeholder='New Password Confirmation' minlength='8' value='' :$model />
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
