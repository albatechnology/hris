<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Timeoff</h3>
            </div>

            <div class="card-body">
                <x-forms.select-collection name='user_id' label='User' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\User::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.select-collection name='timeoff_policy_id' label='Timeoff policy' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\TimeoffPolicy::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.select-array name='request_type' label='Request type' placeholder='-- Please Select --' required='Required' :options="\App\Enums\TimeoffRequestType::all()" :$model />
                <x-forms.input-time name='start_at' label='Start at' placeholder='Start at' required :$model />
                <x-forms.input-time name='end_at' label='End at' placeholder='End at' required :$model />
                <x-forms.input-text name='reason' label='Reason' placeholder='Reason' required :$model />
                <x-forms.select-collection name='delegate_to' label='Delegate to' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\User::class)->get()" optionValue="id" optionLabel="name" :$model />
            </div>
        </div>
    </div>
</div>
