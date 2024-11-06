<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::all()->each(function (Company $company) {

        });
    }
}
