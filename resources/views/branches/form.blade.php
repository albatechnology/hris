<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Branch</h3>
            </div>

            <div class="card-body">
                <x-forms.select-collection name='company_id' label='Company' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\Company::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.input-text name='name' label='Name' placeholder='Name' required :$model />
                <x-forms.input-text name='address' label='Address' placeholder='Address' required :$model />
                <x-forms.input-text name='country' label='country' placeholder='Country' required :$model />
                <x-forms.input-text name='province' label='Province' placeholder='Province' required :$model />
                <x-forms.input-text name='city' label='City' placeholder='City' required :$model />
                <x-forms.input-text name='zip_code' label='Zip_code' placeholder='Zip_code' required :$model />
                <x-forms.input-text name='lat' label='Lat' placeholder='Lat' required :$model />
                <x-forms.input-text name='lng' label='Lng' placeholder='Lng' required :$model />
            </div>
        </div>
    </div>
</div>