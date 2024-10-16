<?php

namespace App\Enums;

enum CountrySettingKey: string
{
    use BaseEnum;

    case BPJS_KESEHATAN_MAXIMUM_SALARY = 'bpjs_kesehatan_maximum_salary';
    case COMPANY_BPJS_KESEHATAN_PERCENTAGE = 'company_bpjs_kesehatan_percentage';
    case EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE = 'employee_bpjs_kesehatan_percentage';
    
    case COMPANY_JKM_PERCENTAGE = 'company_jkm_percentage';

    case COMPANY_JHT_PERCENTAGE = 'company_jht_percentage';
    case EMPLOYEE_JHT_PERCENTAGE = 'employee_jht_percentage';

    case JP_MAXIMUM_SALARY = 'jp_maximum_salary';
    case COMPANY_JP_PERCENTAGE = 'company_jp_percentage';
    case EMPLOYEE_JP_PERCENTAGE = 'employee_jp_percentage';
}