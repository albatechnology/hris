<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Group</h3>
            </div>

            <div class="card-body">
                <x-forms.input-text name='name' label='Name' placeholder='Name' minlength='3' required='Required, at least 3 characters' :$model />
            </div>
        </div>
    </div>
</div>