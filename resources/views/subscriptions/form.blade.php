<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Subscription</h3>
            </div>

            <div class="card-body">
                <x-forms.input-text name='name' label='Name' placeholder='Name' minlength='3'
                    required='Required, at least 3 characters' :$model />
                <x-forms.input-email name='email' label='Email' placeholder='Email' required='Required' :$model />
                <x-forms.input-text name='phone' label='Phone' placeholder='Phone' minlength='10'
                    required='Required, at least 10 characters' :$model />
                <x-forms.input-text name='company_name' label='Company Name' placeholder='Company Name' minlength='3'
                    required='Required, at least 3 characters' :$model />
                <x-forms.input-textarea name='company_address' label='Company Address' placeholder='Company Address'
                    minlength='3' required='Required, at least 3 characters' :$model />
                <x-forms.input-date name='active_end_date' label='Active Until' placeholder='Active Until'
                    :$model />
                <x-forms.input-number name='max_companies' label='Max Companies' placeholder='Max Companies'
                    min="1" :$model />
                <x-forms.input-number name='max_users' label='Max Users' placeholder='Max Users' min="1"
                    :$model />
                <x-forms.input-number name='price' label='Price' placeholder='Price' min="1" :$model />
                <x-forms.input-number name='discount' label='Discount' placeholder='Discount' min="1" :$model />
            </div>
        </div>
    </div>
</div>
