<?php

namespace App\Enums;

use App\Models\User;

enum RequestChangeDataType: string
{
    use BaseEnum;

    case NAME = 'name';
    case LAST_NAME = 'last_name';
    case EMAIL = 'email';
    case WORK_EMAIL = 'work_email';
    case NIK = 'nik';
    case PHONE = 'phone';
    case POSTAL_CODE = 'postal_code';
    case NO_KTP = 'no_ktp';
    case KK_NO = 'kk_no';
    case ADDRESS = 'address';
    case ADDRESS_KTP = 'address_ktp';
    case BIRTH_PLACE = 'birth_place';
    case BIRTHDATE = 'birthdate';
    case GENDER = 'gender';
    case MARITAL_STATUS = 'marital_status';
    case BLOOD_TYPE = 'blood_type';
    case RHESUS = 'rhesus';
    case RELIGION = 'religion';
    case BPJS_KETENAGAKERJAAN_NO = 'bpjs_ketenagakerjaan_no';
    case BPJS_KESEHATAN_NO = 'bpjs_kesehatan_no';
    case BPJS_KESEHATAN_FAMILY_NO = 'bpjs_kesehatan_family_no';
    case NPWP = 'npwp';
    case BANK_ACCOUNT_NO = 'bank_account_no';
    case BANK_NAME = 'bank_name';
    case BANK_ACCOUNT_HOLDER = 'bank_account_holder';
    case SECONDARY_BANK_ACCOUNT_NO = 'secondary_bank_account_no';
    case SECONDARY_BANK_NAME = 'secondary_bank_name';
    case SECONDARY_BANK_ACCOUNT_HOLDER = 'secondary_bank_account_holder';
    case PHOTO_PROFILE = 'photo_profile';
    case PTKP_STATUS = 'ptkp_status';

    public function getValidation(?User $user = null)
    {
        return match ($this) {
            self::EMAIL => 'required|email|unique:users,email,' . $user->id,
            self::BIRTHDATE => 'required|date',
            self::GENDER => ['required', \Illuminate\Validation\Rule::enum(Gender::class)],
            self::MARITAL_STATUS => ['required', \Illuminate\Validation\Rule::enum(MaritalStatus::class)],
            self::BLOOD_TYPE => ['required', \Illuminate\Validation\Rule::enum(BloodType::class)],
            self::RELIGION => ['required', \Illuminate\Validation\Rule::enum(Religion::class)],
            self::PTKP_STATUS => ['required', \Illuminate\Validation\Rule::enum(PtkpStatus::class)],
            self::PHOTO_PROFILE => 'required|mimes:' . config('app.file_mimes_types'),
            default => 'required|string'
        };
    }

    public static function updateData(self $self, int $userId, mixed $value)
    {
        return match ($self) {
            // self::PHOTO_PROFILE, updated in controller
            self::NAME,
            self::LAST_NAME,
            self::EMAIL,
            self::WORK_EMAIL,
            self::NIK,
            self::PHONE,
            self::GENDER => \App\Models\User::where('id', $userId)->update([$self->value => $value]),

            self::NO_KTP,
            self::ADDRESS,
            self::ADDRESS_KTP,
            self::POSTAL_CODE,
            self::BIRTH_PLACE,
            self::BIRTHDATE,
            self::MARITAL_STATUS,
            self::BLOOD_TYPE,
            self::RHESUS,
            self::RELIGION => \App\Models\UserDetail::where('user_id', $userId)->update([$self->value => $value]),

            self::BPJS_KETENAGAKERJAAN_NO,
            self::BPJS_KESEHATAN_NO,
            self::BPJS_KESEHATAN_FAMILY_NO,
            self::NPWP,
            self::BANK_ACCOUNT_NO,
            self::BANK_NAME,
            self::BANK_ACCOUNT_HOLDER,
            self::SECONDARY_BANK_ACCOUNT_NO,
            self::SECONDARY_BANK_NAME,
            self::SECONDARY_BANK_ACCOUNT_HOLDER,
            self::PTKP_STATUS => \App\Models\UserPayrollInfo::where('user_id', $userId)->update([$self->value => $value]),
        };
    }

    public function getValue(?int $userId = null)
    {
        if (!$userId) $userId = auth('sanctum')->id();

        return match ($this) {
            self::PHOTO_PROFILE => \App\Models\User::where('id', $userId)->first(['id'])->getFirstMediaUrl(MediaCollection::USER->value),
            self::NAME,
            self::LAST_NAME,
            self::EMAIL,
            self::WORK_EMAIL,
            self::NIK,
            self::PHONE,
            self::GENDER => \App\Models\User::where('id', $userId)->first([$this->value])->{$this->value},

            self::NO_KTP,
            self::ADDRESS,
            self::ADDRESS_KTP,
            self::POSTAL_CODE,
            self::BIRTH_PLACE,
            self::BIRTHDATE,
            self::MARITAL_STATUS,
            self::BLOOD_TYPE,
            self::RHESUS,
            self::RELIGION => \App\Models\UserDetail::where('user_id', $userId)->first([$this->value])->{$this->value},

            self::BPJS_KETENAGAKERJAAN_NO,
            self::BPJS_KESEHATAN_NO,
            self::BPJS_KESEHATAN_FAMILY_NO,
            self::NPWP,
            self::BANK_ACCOUNT_NO,
            self::BANK_NAME,
            self::BANK_ACCOUNT_HOLDER,
            self::SECONDARY_BANK_ACCOUNT_NO,
            self::SECONDARY_BANK_NAME,
            self::SECONDARY_BANK_ACCOUNT_HOLDER,
            self::PTKP_STATUS => \App\Models\UserPayrollInfo::where('user_id', $userId)->first([$this->value])->{$this->value},
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
            // self::BIRTHDATE => 'date',

            self::GENDER => Gender::all(),

            self::MARITAL_STATUS => MaritalStatus::all(),
            self::BLOOD_TYPE => BloodType::all(),
            self::RELIGION => Religion::all(),

            self::PTKP_STATUS => PtkpStatus::all(),
            default => ''
        };
    }
}
