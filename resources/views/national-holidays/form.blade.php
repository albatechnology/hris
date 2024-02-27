<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create National Holiday</h3>
            </div>

            <div class="card-body">
                <x-forms.input-text name='name' label='Name' placeholder='Name' required :$model />
                <x-forms.input-date name='date' label='date' placeholder='date' required :$model />
            </div>
        </div>
    </div>
</div>