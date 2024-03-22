<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Overtime</h3>
            </div>

            <div class="card-body">
                <x-forms.select-collection name='company_id' label='Company' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\Company::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.input-text name='name' label='Name' placeholder='Name' required :$model />
                <x-forms.select-array name='is_rounding' label='Is rounding' placeholder='-- Please Select --' required='Required' :options="\App\Enums\BooleanValue::all()" :$model />
                <x-forms.input-number name='compensation_rate_per_day' label='Compensation rate per day' placeholder='Compensation rate per day' required :$model />
                <x-forms.select-array name='rate_type' label='Rate type' placeholder='-- Please Select --' required='Required' :options="\App\Enums\RateType::all()" :$model />
                <x-forms.input-number name='rate_amount' label='Rate amount' placeholder='Rate amount' required :$model />
            </div>
        </div>
    </div>
</div>