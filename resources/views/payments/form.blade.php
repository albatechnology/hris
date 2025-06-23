<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Payment</h3>
            </div>

            <div class="card-body">
                <x-forms.select-collection name='group_id' label='Group' placeholder='-- Please Select --'
                    required='Required' :options="app(\App\Models\Group::class)->select('id', 'name')->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.input-date name='active_end_date' label='Active Until' placeholder='Active Until'
                    :$model />
                <x-forms.input-time name='payment_at' label='Payment At' placeholder='Payment At' :$model />
                <x-forms.input-number name='total_price' label='Total Price' placeholder='Total Price' min="1" :$model />
            </div>
        </div>
    </div>
</div>
