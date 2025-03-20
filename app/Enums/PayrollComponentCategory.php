<?php

namespace App\Enums;

enum PayrollComponentCategory: string
{
    use BaseEnum;

    case DEFAULT = 'default';
    case BASIC_SALARY = 'basic_salary';
    case OVERTIME = 'overtime';
    case TASK_OVERTIME = 'task_overtime';
    case ALPA = 'alpa';
    case LOAN = 'loan';
    case INSURANCE = 'insurance';

    case BPJS_KESEHATAN = 'bpjs_kesehatan';
    case BPJS_KETENAGAKERJAAN = 'bpjs_ketenagakerjaan';

    case COMPANY_BPJS_KESEHATAN = 'company_bpjs_kesehatan';
    case EMPLOYEE_BPJS_KESEHATAN = 'employee_bpjs_kesehatan';

    case COMPANY_JKK = 'company_jkk';

    case COMPANY_JKM = 'company_jkm';

    case COMPANY_JHT = 'company_jht';
    case EMPLOYEE_JHT = 'employee_jht';

    case COMPANY_JP = 'company_jp';
    case EMPLOYEE_JP = 'employee_jp';
}
