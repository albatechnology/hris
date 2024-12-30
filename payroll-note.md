1. setting payroll di setiap user, termasuk overtime. set overtime_id di table users
2. cek payroll components(allowance, deduction, benefit), udah ada apa belum. siapa tau belum ada
3. cek payroll setting udah ada apa belum
4. country setting


step run payroll
- fungsi setup payroll component(alowance|deduction|benefit) adalah sebagai acuan komponen payroll yang akan jadi perhitungan di run payroll. jadi hasil run payroll nya hanya berisi komponen yang telah di setup pada saat menjalankan run payroll, selain itu tidak akan jadi perhitungan, berlaku juga untuk perhitungan update payroll component.
- untuk saat ini karena ada perhitungan BPJS, maka harus cek dulu apakah company tsb memiliki CountrySetting dan memiliki semua data CountrySettingKey. jika tidak ada return error
- create data run_payroll untuk menjadi header nya
- create run_payroll_details dengan cara looping users nya
- sebelumnya yg pertama dihitung adalah default payroll component
 $defaultPayrollComponents = PayrollComponent::tenanted()->whereCompany($request['company_id'])->whereDefault()->get();
 untuk saat ini basic salary dulu yang pertama dihitung. mungkin kedepan bisa berubah
 jangan lupa hitung prorate
## OKEOKE
- perhitungan prorate
    - perhitungan prorate akan terjadi jika terdapat update payroll component basic salary (jika effective_date nya sudah melebihi)
    - apabila terdapat update_payroll_components DAN effective_date update_payroll_components nya masih di periode saat run payroll, maka prorate akan dihitung sebagai berikut
        - jika effective_date dan end_date berada dalam 1 periode, maka perhitungan gaji akan dibagi menjadi 3 bagian lalu di total (kondisi ini otomatis berlaku apabila effective_date === end_date)
            1. gaji dari awal periode hingga effective_date menggunakan basic_salary (effective_date tidak dihitung / minus 1 hari)
            2. gaji dari effective_date hingga end_date menggunakan gaji baru (end_date dihitung)
            3. gaji dari end_date hingga akhir periode menggunakan basic_salary (end_date tidak dihitung, akhir periode dihitung / minus 1 hari)
        <!-- - jika terdapat end_date
            - jika effective_date dan end_date berada dalam 1 periode, maka perhitungan gaji akan dibagi menjadi 3 bagian lalu di total (kondisi ini otomatis berlaku apabila effective_date === end_date)
                1. gaji dari awal periode hingga effective_date menggunakan basic_salary (effective_date tidak dihitung / minus 1 hari)
                2. gaji dari effective_date hingga end_date menggunakan gaji baru (end_date dihitung)
                3. gaji dari end_date hingga akhir periode menggunakan basic_salary (end_date tidak dihitung, akhir periode dihitung / minus 1 hari)
            - jika effective_date dan end_date TIDAK berada dalam 1 periode, maka perhitungan gaji akan dibagi menjadi 2 bagian lalu di total
                1. gaji dari awal periode hingga effective_date (effective_date tidak dihitung / minus 1 hari)
                2. gaji dari effective_date hingga akhir periode -->
        - jika tidak terdapat end_date maka perhitungannya sama seperti diatas
    - apabila terdapat update_payroll_components DAN effective_date update_payroll_components nya diluar periode saat run payroll, maka prorate akan dihitung sebagai berikut
        - jika terdapat end_date dan end_date nya ada di periode tersebut
            1. gaji dari awal periode hingga end_date menggunakan gaji baru (end_date dihitung)
            2. gaji dari end_date hingga akhir periode menggunakan basic_salary user (end_date tidak dihitung / minus 1 hari)
            <!-- - jika end_date === awal periode maka gaji baru hanya dihitung 1 hari
               1. gaji dari awal periode hingga end_date menggunakan gaji baru (1 hari gaji)
               2. gaji dari end_date hingga akhir periode menggunakan basic_salary user
            - else
               1. gaji dari awal periode hingga end_date menggunakan gaji baru (end_date dihitung)
               2. gaji dari end_date hingga akhir periode menggunakan basic_salary user (end_date tidak dihitung / minus 1 hari) -->
        - jika tidak terdapat end_date maka dalam satu periode run payroll tersebut akan menggunakan gaji baru



    - (else point 2) basic salary akan menggunakan component yang ada di update_payroll_components

    - jika terdapat end_date, maka hitung lagi prorate nya berdasarkan end_date nya
    - jika tidak terdapat end_date makan akan digunakan selamanya

- hitung semua payroll component (alowance|deduction|benefit), kecuali component yang is_default=0
- hitung ALPA
- hitung component bpjs
- hitung overtime

updates
- remove setting / PayrollComponentSetting from payroll_components
- remove is_daily_default, daily_maximum_amount_type, daily_maximum_amount from payroll_components
- remove cutoff_attendance_start_date, cutoff_attendance_end_date from payroll_setting
- change payroll_schedule_date to cut_off_date from payroll_setting, for determine payroll cut off date
