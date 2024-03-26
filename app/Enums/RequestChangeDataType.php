<?php

namespace App\Enums;

use App\Models\User;
use App\Models\UserDetail;
use App\Models\UserPayrollInfo;

enum RequestChangeDataType: string
{
    use BaseEnum;

    case NAME = 'name';
    case EMAIL = 'email';
    case NIK = 'nik';
    case PHONE = 'phone';
    case ADDRESS = 'address';
    case BIRTH_PLACE = 'birth_place';
    case BIRTHDATE = 'birthdate';
    case GENDER = 'gender';
    case MARITAL_STATUS = 'marital_status';
    case BLOOD_TYPE = 'blood_type';
    case RELIGION = 'religion';
    case BPJS_KETENAGAKERJAAN_NO = 'bpjs_ketenagakerjaan_no';
    case BPJS_KESEHATAN_NO = 'bpjs_kesehatan_no';
    case BPJS_KESEHATAN_FAMILY_NO = 'bpjs_kesehatan_family_no';
    case NPWP = 'npwp';
    case BANK_ACCOUNT_NO = 'bank_account_no';
    case BANK_NAME = 'bank_name';
    case BANK_ACCOUNT_HOLDER = 'bank_account_holder';
    case PHOTO_PROFILE = 'photo_profile';
    case PTKP_STATUS = 'ptkp_status';

    public function getValue(?int $userId = null)
    {
        if (!$userId) $userId = auth('sanctum')->id();

        return match ($this) {
            self::PHOTO_PROFILE => User::where('id', $userId)->first([$this->value]),
            self::NAME => User::where('id', $userId)->first([$this->value]),
            self::EMAIL => User::where('id', $userId)->first([$this->value]),
            self::NIK => User::where('id', $userId)->first([$this->value]),
            self::PHONE => User::where('id', $userId)->first([$this->value]),
            self::GENDER => User::where('id', $userId)->first([$this->value]),

            self::ADDRESS => UserDetail::where('user_id', $userId)->first([$this->value]),
            self::BIRTH_PLACE => UserDetail::where('user_id', $userId)->first([$this->value]),
            self::BIRTHDATE => UserDetail::where('user_id', $userId)->first([$this->value]),
            self::MARITAL_STATUS => UserDetail::where('user_id', $userId)->first([$this->value]),
            self::BLOOD_TYPE => UserDetail::where('user_id', $userId)->first([$this->value]),
            self::RELIGION => UserDetail::where('user_id', $userId)->first([$this->value]),

            self::BPJS_KETENAGAKERJAAN_NO => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::BPJS_KESEHATAN_NO => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::BPJS_KESEHATAN_FAMILY_NO => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::NPWP => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::BANK_ACCOUNT_NO => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::BANK_NAME => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::BANK_ACCOUNT_HOLDER => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
            self::PTKP_STATUS => UserPayrollInfo::where('user_id', $userId)->first([$this->value]),
        };
    }

    public function getInputType()
    {
        return match ($this) {
            self::PHOTO_PROFILE => 'file',
            self::BIRTHDATE => 'date',

            self::GENDER,

            self::MARITAL_STATUS,
            self::BLOOD_TYPE,
            self::RELIGION,

            self::PTKP_STATUS => 'select',
            default => 'text'
        };
    }

    public function getInputValue()
    {
        return match ($this) {
            self::PHOTO_PROFILE => 'file',
            self::BIRTHDATE => 'date',

            self::GENDER => Gender::all(),

            self::MARITAL_STATUS => MaritalStatus::all(),
            self::BLOOD_TYPE => BloodType::all(),
            self::RELIGION => Religion::all(),

            self::PTKP_STATUS => PtkpStatus::all(),
            default => ''
        };
    }
}
