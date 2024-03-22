<div class="row">
    <div class="col-md-12">
        <div class="card card-orange">
            <div class="card-header">
                <h3 class="card-title">Create Attendance</h3>
            </div>

            <div class="card-body">
                <x-forms.select-collection name='user_id' label='User' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\User::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.select-collection name='schedule_id' label='Schedule' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\Schedule::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.select-collection name='shift_id' label='Shift' placeholder='-- Please Select --' required='Required' :options="app(\App\Models\Shift::class)->get()" optionValue="id" optionLabel="name" :$model />
                <x-forms.select-array name='is_clock_in' label='Is Clock In' placeholder='-- Please Select --' required='Required' :options="\App\Enums\BooleanValue::all()" :$model />
                <x-forms.input-time name='time' label='Time' placeholder='Time' required :$model />
                <x-forms.select-array name='type' label='Type' placeholder='-- Please Select --' required='Required' :options="\App\Enums\AttendanceType::all()" :$model />
                <x-forms.input-text name='lat' label='Lat' placeholder='Lat' required :$model />
                <x-forms.input-text name='lng' label='Lng' placeholder='Lng' required :$model />
                <x-forms.input-text name='note' label='Note' placeholder='Note' required :$model />
                <x-forms.input-file name='file' label='File' required :$model />
            </div>
        </div>
    </div>
</div>