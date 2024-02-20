<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::all()->each(function ($company) {
            $company->customFields()->create([
                'key' => 'Nama Kucing Peliharaan',
                'type' => 'text'
            ]);

            $company->customFields()->create([
                'key' => 'Tgl Lahir Kucing Peliharaan',
                'type' => 'date'
            ]);

            $company->customFields()->create([
                'key' => 'Ukuran Jaket',
                'type' => 'select',
                'options' => ['S', 'M', 'L', 'XL', 'XXL'],
            ]);
        });
    }
}
