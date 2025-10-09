<?php

namespace App\Enums;

enum ResignationReason: string
{
    use BaseEnum;

        // sunshine
    case RESIGNED_VOLUNTARILY_WITHOUT_PRESSURE_IN_COMPLIANCE = "Resigned voluntarily without pressure, in compliance with company regulations";
    case RESIGNED_VOLUNTARILY_WITHOUT_PRESSURE_NOT_IN_COMPLIANCE = "Resigned voluntarily without pressure, not in compliance with company regulations";

        // lumora
    case RESIGNED_VOLUNTARILY_WITHOUT_PRESSURE = "Resigned voluntarily without pressure";
    case EMPLOYEE_VIOLATED_AGREEMENT = "Employee violated the employment agreement, collective labor agreement, or company regulations";
    case EMPLOYEE_REQUESTED_TERMINATION = "Employee requested termination due to employer's violation";
    case EMPLOYEE_DOES_NOT_WISH_TO_CONTINUE = "Merger, consolidation, or change in company status, and the employee does not wish to continue the employment relationship";
    case EMPLOYER_DOES_NOT_WISH_TO_CONTINUE = "Merger, consolidation, or change in company status, and the employer does not wish to continue the employment relationship";
    case COMPANY_BANKRUPTCY = "Company bankruptcy";
    case EMPLOYEE_WAS_ABSENT_FOR_5_DAYS_OR_MORE = "Employee was absent for 5 days or more without notice and has been properly summoned twice";
    case EMPLOYEE_SUFFERED_FROM_PROLONGED_ILLNESS = "Employee suffers from prolonged illness or work-related accident (after 12 months)";
    case TERMINATION_WITHOUT_EMPLOYEE_VIOLATING_AGREEMENT = "Termination without the employee violating the Employment Agreement, Collective Labor Agreement, or Company Regulations";
    case COMPANY_CONDUCTS_DOWNSIZING_OR_CLOSES_DUE_TO_LOSSES = "Company conducts downsizing or closes due to losses";
    case COMPANY_CONDUCTS_DOWNSIZING_TO_PREVENT_LOSSES = "Company conducts downsizing to prevent losses";
    case COMPANY_ACQUISITION_AND_EMPLOYEE_DOES_NOT_AGREE_TO_CONTINUE = "Company acquisition and the employee does not agree to continue the employment relationship";
    case COMPANY_CLOSES_NOT_DUE_TO_FINANCIAL_LOSSES = "Company closes not due to financial losses";
    case TERMINATION_DUE_TO_FORCE_MAJEURE = "Termination due to force majeure and the company closes";
    case TERMINATION_DUE_TO_FORCE_MAJEURE_BUT_COMPANY_DOES_NOT_CLOSE = "Termination due to force majeure but the company does not close";
    case COMPANY_IS_UNDER_SUSPENSION_OF_DEBT_PAYMENT_DUE_TO_LOSSES = "Company is under suspension of debt payment due to losses";
    case COMPANY_IS_UNDER_SUSPENSION_OF_DEBT_PAYMENT_NOT_DUE_TO_LOSSES = "Company is under suspension of debt payment not due to losses";
    case EMPLOYEE_IS_DETAINED_AND_CAUSES_LOSS = "Employee is detained and unable to work (after 6 months) and causes loss to the company";
    case EMPLOYEE_IS_DETAINED_BUT_DOES_NOT_CAUSE_LOSS = "Employee is detained and unable to work (after 6 months) but does not cause loss to the company";
    case EMPLOYEE_IS_DETAINED_AND_FOUND_GUILTY_CAUSING_LOSS = "Employee is detained and found guilty, causing loss to the company";
    case EMPLOYEE_IS_DETAINED_AND_FOUND_GUILTY_BUT_DOES_NOT_CAUSE_LOSS = "Employee is detained and found guilty but does not cause loss to the company";
    case EMPLOYEE_COMMITS_AN_URGENT_VIOLATION = "Employee commits an urgent violation as regulated in the employment agreement, company regulations, or other agreements";

        // all
    // case DID_NOT_PASS_PROBATION_PERIOD = "Did not pass the probation period";
    case DID_NOT_PASS_CONTRACT_PERIOD = "Did not pass the contract period";
    case TERMINATED_BY_SUPERVISOR = "Terminated by supervisor";
    case COMPLETION_OF_FIXED_TERM_AGREEMENT = "Completion of a fixed-term employment agreement (PKWT)";
    case PASSED_AWAY = "Employee passed away";
    case REACHES_RETIREMENT_AGE = "Employee reaches retirement age";
}
