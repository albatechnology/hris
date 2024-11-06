<?php

namespace Database\Seeders;

use App\Models\Company;
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
                'key' => 'Ukuran Batik SUN',
                'type' => 'select',
                'options' => ['S', 'M', 'L', 'XL', 'XXL'],
            ]);

            $company->customFields()->create([
                'key' => 'Ukuran Kaos DBMI',
                'type' => 'select',
                'options' => ['S', 'M', 'L', 'XL', 'XXL'],
            ]);
        });
    }
}
