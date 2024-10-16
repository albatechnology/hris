<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Staff',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Manager',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Direktur',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // $division = new Division([
        //     'name' => 'Operasional',
        //     'created_at' => now(),
        //     'updated_at' => now(),
        // ]);
        $departments = [
            [
                'name' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HR',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $group = Group::create([
            'name' => 'SUN EDUCATION GROUP',
        ]);

        $company = $group->companies()->create([
            'name' => 'SUN INDONESIA',
            'address' => 'Gedung SUN Plaza, Jl. Meruya Ilir Raya No.20, RT.4/RW.7, Meruya Sel., Kec. Kembangan, Kota Jakarta Barat, Daerah Khusus Ibukota Jakarta 11610',
            'country_id' => 1,
            'country' => 'Indonesia',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Barat',
            'zip_code' => '11610',
            'lat' => '-6.1979899',
            'lng' => '106.742916',
        ]);
        $company->positions()->createMany($positions);
        $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        $division->departments()->createMany($departments);
        $company->branches()->create([
            'name' => 'SUN Plaza',
            'address' => 'Gedung SUN Plaza, Jl. Meruya Ilir Raya No.20, RT.4/RW.7, Meruya Sel., Kec. Kembangan, Kota Jakarta Barat, Daerah Khusus Ibukota Jakarta 11610',
            'country' => 'Indonesia',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Barat',
            'zip_code' => '11610',
            'lat' => '-6.1979899',
            'lng' => '106.742916',
            'umk' => 5000000,
        ]);
        $company->branches()->create([
            'name' => 'SUN Alam Sutera',
            'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
            'country' => 'Indonesia',
            'province' => 'Banten',
            'city' => 'Kota Tangerang Selatan',
            'zip_code' => '15320',
            'lat' => '-6.2348656',
            'lng' => '106.5766001',
            'umk' => 5000000,
        ]);

        $company = $group->companies()->create([
            'name' => 'SUN ASA EDUCATION',
            'address' => 'Sunway Geo Avenue, Sunway South Quay, Jalan Lagoon Selatan, Bandar Sunway, 47500 Subang Jaya, Selangor, Malaysia',
            'country_id' => 1,
            'country' => 'Malaysia',
            'province' => 'Selangor',
            'city' => 'Subang Jaya',
            'zip_code' => '47500',
            'lat' => '3.0649282',
            'lng' => '101.5256048',
        ]);
        $company->positions()->createMany($positions);
        $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        $division->departments()->createMany($departments);
        $company->branches()->create([
            'name' => 'SUNWAY Geo',
            'address' => 'Sunway Geo Avenue, Sunway South Quay, Jalan Lagoon Selatan, Bandar Sunway, 47500 Subang Jaya, Selangor, Malaysia',
            'country' => 'Malaysia',
            'province' => 'Selangor',
            'city' => 'Subang Jaya',
            'zip_code' => '47500',
            'lat' => '3.0649282',
            'lng' => '101.5256048',
            'umk' => 5000000,
        ]);
        $company->branches()->create([
            'name' => 'SUN KL',
            'address' => 'Lebuh Bandar Utama Centre Point, Bandar Utama, 47800 Petaling Jaya, Selangor, Malaysia',
            'country' => 'Malaysia',
            'province' => 'Selangor',
            'city' => 'Petaling Jaya',
            'zip_code' => '47800',
            'lat' => '3.1380439',
            'lng' => '101.5275401',
            'umk' => 5000000,
        ]);

        $group = Group::create([
            'name' => 'ALBA GROUP',
        ]);

        $company = $group->companies()->create([
            'name' => 'Alba Digital Technology',
            'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
            'country_id' => 1,
            'country' => 'Indonesia',
            'province' => 'Banten',
            'city' => 'Kota Tangerang Selatan',
            'zip_code' => '15143',
            'lat' => '-6.2326902',
            'lng' => '106.6645009',
        ]);
        $company->positions()->createMany($positions);
        $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        $division->departments()->createMany($departments);
        $company->branches()->create([
            'name' => 'Alba Alam Sutera',
            'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
            'country' => 'Indonesia',
            'province' => 'Banten',
            'city' => 'Kota Tangerang Selatan',
            'zip_code' => '15320',
            'lat' => '-6.2348656',
            'lng' => '106.5766001',
            'umk' => 5000000,
        ]);
        // $company->branches()->create([
        //     'name' => 'Alba Eliafood',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5000000,
        // ]);


        // GROUP PATROL
        $group = Group::create([
            'name' => 'Group Patrol',
        ]);

        $company = $group->companies()->create([
            'name' => 'PT. Patroli 86',
            'address' => 'Jl. Patroli 86, Kebon Pala, Kec. Gambir, Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10110',
            'country_id' => 1,
            'country' => 'Indonesia',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Barat',
            'zip_code' => '11610',
            'lat' => '-6.1979899',
            'lng' => '106.742916',
        ]);
        $company->positions()->createMany($positions);
        $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        $division->departments()->createMany($departments);
        $company->branches()->create([
            'name' => 'Branch Patroli 1',
            'address' => 'Jl. Patroli 86, Kebon Pala, Kec. Gambir, Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10110',
            'country' => 'Indonesia',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Barat',
            'zip_code' => '11610',
            'lat' => '-6.1979899',
            'lng' => '106.742916',
            'umk' => 5000000,
        ]);
    }
}
