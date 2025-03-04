<?php

namespace Database\Seeders;

use App\Enums\CountrySettingKey;
use App\Models\Country;
use App\Models\CountrySetting;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Country::insert([
      [
        'name' => 'Indonesia',
      ],
      [
        'name' => 'Malaysia',
      ],
    ]);

    foreach (Country::all() as $country) {
      CountrySetting::insert([
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::BPJS_KESEHATAN_MAXIMUM_SALARY,
          'value' => 12000000,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::COMPANY_BPJS_KESEHATAN_PERCENTAGE,
          'value' => 4,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::EMPLOYEE_BPJS_KESEHATAN_PERCENTAGE,
          'value' => 1,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::COMPANY_JKM_PERCENTAGE,
          'value' => 0.30,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::COMPANY_JHT_PERCENTAGE,
          'value' => 3.79,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::EMPLOYEE_JHT_PERCENTAGE,
          'value' => 2,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::JP_MAXIMUM_SALARY,
          'value' => 10547400,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::COMPANY_JP_PERCENTAGE,
          'value' => 2,
        ],
        [
          'country_id' => $country->id,
          'key' => CountrySettingKey::EMPLOYEE_JP_PERCENTAGE,
          'value' => 1,
        ],
      ]);
    }
  }
}
