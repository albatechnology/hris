check fungsi job_position dan job_level di user_details
api /users/{id}/detail sudah tidak kirim positions, instead position_id and department_id
file UserInitializeService dibagian positions nya belum tau mau gimana, soalnya position nya ga ada datanya
hapus UserDepartmentPosition karena sudah di move ke table users
delete table user_department_positions
position_access dkk harusnya dijadiin satu aja
