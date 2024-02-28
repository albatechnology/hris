@extends('layouts.base')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Profile</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3">

                <!-- Profile Image -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <h3 class="profile-username text-center">{{ $model->name }}</h3>
                        <p class="text-muted text-center">{{ $model->type->getLabel() }}</p>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item ">
                                <b>Phone</b> <a class="float-right text-dark">{{ $model->phone }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Email</b> <a class="float-right text-dark">{{ $model->email }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Last Update</b> <a class="float-right text-dark">{{ $model->updated_at }}</a>
                            </li>
                        </ul>

                        <a href="{{ route('users.edit', $model->id) }}" class="btn btn-warning btn-block"><i class="fas fa-edit"></i> <b>Edit</b></a>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
            <!-- /.col -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header p-2">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a class="nav-link active" href="#basic-info" data-toggle="tab">Basic Info</a></li>
                            <li class="nav-item"><a class="nav-link" href="#contacts" data-toggle="tab">Contacts</a></li>
                            <li class="nav-item"><a class="nav-link" href="#educations" data-toggle="tab">Educations</a></li>
                            <li class="nav-item"><a class="nav-link" href="#experiences" data-toggle="tab">Experiences</a></li>
                            <li class="nav-item"><a class="nav-link" href="#additional-info" data-toggle="tab">Additional Info</a></li>
                        </ul>
                    </div><!-- /.card-header -->
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="active tab-pane" id="basic-info">
                                <div>
                                    <p class="h4 pb-2 mb-2 text-dark border-bottom border-dark">Personal Data</p>
                                </div>
                                <div class="card-body box-profile">
                                    <table class="h5">
                                        <tr>
                                            <td width="300">Job position</td>
                                            <td>: {{ $model->detail->job_position }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Job level</td>
                                            <td>: {{ $model->detail->job_level }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Employment status</td>
                                            <td>: {{ $model->detail->employment_status }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Join date</td>
                                            <td>: {{ $model->detail->join_date }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Sign date</td>
                                            <td>: {{ $model->detail->sign_date }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Birth place</td>
                                            <td>: {{ $model->detail->birth_place }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Birthdate</td>
                                            <td>: {{ $model->detail->birthdate }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Marital status</td>
                                            <td>: {{ $model->detail->marital_status }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Blood type</td>
                                            <td>: {{ $model->detail->blood_type }}</td>
                                        </tr>
                                        <tr>
                                            <td width="300">Religion</td>
                                            <td>: {{ $model->detail->religion }}</td>
                                        </tr>
                                    </table>
                                    <div>
                                        <p class="h4 pb-2 mt-5 text-dark border-bottom border-dark">Identity & Address</p>
                                    </div>
                                    <div class="active tab-pane" id="basic-info">
                                        <div class="card-body box-profile">
                                            <table class="h5">
                                                <tr>
                                                    <td width="300">No KTP</td>
                                                    <td>: {{ $model->detail->no_ktp }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">No KK</td>
                                                    <td>: {{ $model->detail->kk_no }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">Address</td>
                                                    <td>: {{ $model->detail->address }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">Address KTP</td>
                                                    <td>: {{ $model->detail->address_ktp }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">No passport</td>
                                                    <td>: {{ $model->detail->passport_no }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">Passport expired</td>
                                                    <td>: {{ $model->detail->passport_expired }}</td>
                                                </tr>
                                                <tr>
                                                    <td width="300">Type</td>
                                                    <td>: {{ $model->type->getLabel() }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="contacts">
                                <ul class="nav nav-pills">
                                    <li class="nav-item"><a class="nav-link active" href="#contacts-family" data-toggle="tab">Family</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#contacts-emergency" data-toggle="tab">Emergency</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="contacts-family">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">ID number</th>
                                                    <th scope="col">Relationship</th>
                                                    <th scope="col">Gender</th>
                                                    <th scope="col">Job</th>
                                                    <th scope="col">Religion</th>
                                                    <th scope="col">Birthdate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($model->contacts()->where('type', 'family')->get() as $i => $contact)
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $contact->name }}</td>
                                                    <td>{{ $contact->id_number }}</td>
                                                    <td>{{ $contact->relationship }}</td>
                                                    <td>{{ $contact->gender }}</td>
                                                    <td>{{ $contact->job }}</td>
                                                    <td>{{ $contact->religion }}</td>
                                                    <td>{{ $contact->birthdate }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="tab-pane" id="contacts-emergency">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Relationship</th>
                                                    <th scope="col">Phone Number</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($model->contacts()->where('type', 'emergency')->get() as $i => $contact)
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $contact->name }}</td>
                                                    <td>{{ $contact->relationship }}</td>
                                                    <td>{{ $contact->phone }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="educations">
                                <ul class="nav nav-pills">
                                    <li class="nav-item"><a class="nav-link active" href="#educations-formal" data-toggle="tab">Formal</a></li>
                                    <li class="nav-item"><a class="nav-link" href="#educations-informal" data-toggle="tab">Informal</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active" id="educations-formal">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Institution Name</th>
                                                    <th scope="col">Level</th>
                                                    <th scope="col">Majors</th>
                                                    <th scope="col">Start Date</th>
                                                    <th scope="col">End Date</th>
                                                    <th scope="col">Score</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($model->educations()->where('type', 'formal')->get() as $i => $edication)
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $edication->institution_name }}</td>
                                                    <td>{{ $edication->level }}</td>
                                                    <td>{{ $edication->majors }}</td>
                                                    <td>{{ $edication->start_date }}</td>
                                                    <td>{{ $edication->end_date }}</td>
                                                    <td>{{ $edication->score }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="tab-pane" id="educations-informal">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Start Date</th>
                                                    <th scope="col">End Date</th>
                                                    <th scope="col">Expired Date</th>
                                                    <th scope="col">Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($model->educations()->where('type', 'informal')->get() as $i => $edication)
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $edication->name }}</td>
                                                    <td>{{ $edication->start_date }}</td>
                                                    <td>{{ $edication->end_date }}</td>
                                                    <td>{{ $edication->expired_date }}</td>
                                                    <td>{{ $edication->fee }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="experiences">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="experiences">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Company</th>
                                                    <th scope="col">Department</th>
                                                    <th scope="col">Position</th>
                                                    <th scope="col">Start Date</th>
                                                    <th scope="col">End Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($model->experiences as $i => $experience)
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $experience->company }}</td>
                                                    <td>{{ $experience->department }}</td>
                                                    <td>{{ $experience->position }}</td>
                                                    <td>{{ $experience->start_date }}</td>
                                                    <td>{{ $experience->end_date }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- /.tab-pane -->
                            <div class="tab-pane" id="additional-info">
                                <div class="tab-content">
                                    <div class="tab-pane active" id="additional-info">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">No</th>
                                                    <th scope="col">Batik size</th>
                                                    <th scope="col">Tshirt size</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">{{ ++$i }}</th>
                                                    <td>{{ $model->detail->batik_size }}</td>
                                                    <td>{{ $model->detail->tshirt_size }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection