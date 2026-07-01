check fungsi job_position dan job_level di user_details
api /users/{id}/detail sudah tidak kirim positions, instead position_id and department_id
file UserInitializeService dibagian positions nya belum tau mau gimana, soalnya position nya ga ada datanya
hapus UserDepartmentPosition karena sudah di move ke table users
delete table user_department_positions
position_access dkk harusnya dijadiin satu aja

===== UPDATE JOB POSITION & LEVEL =====

di table shifts
$data['show_in_request_department_ids'] = $request->department_ids;
$data['show_in_request_position_ids'] = $request->position_ids;
show_in_request_job_position_ids
show_in_request_job_level_ids
cek api CRUD shifts

di table announcements
department_ids dan position_ids perlu diupdate
cek api CRUD announcements

di table users
department_ids dan position_ids perlu diupdate
cek api CRUD users

user_transfers
user_rehire
UserTransferPosition

update organization chart

job_position_id
job_level_id

======= TIMEOFF =======
ExistingEmployee
NewEmployee

TimeoffService

remove ReevaluateTimeOffDisciplineReward
======= TIMEOFF =======
