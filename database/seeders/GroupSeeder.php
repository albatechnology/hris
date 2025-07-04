<?php

namespace Database\Seeders;

use App\Http\Services\Company\CompanyInitializeService;
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

        // $group = Group::create([
        //     'name' => 'ATARA Corp',
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. Argusindo Investama Raharja',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. Argusindo Investama Raharja',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. ATARA RESOURCES CANDRASA',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. ATARA RESOURCES CANDRASA',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. DUTADHARMA UTAMA',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. DUTADHARMA UTAMA',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. AMAN BARATAMA SEJAHTERA',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. AMAN BARATAMA SEJAHTERA',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. BANJAR BUMI PERSADA',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. BANJAR BUMI PERSADA',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. MITRA AGRO SEMESTA',
        //     'address' => 'Alam Sutera, The Prominence, Jl. Jalur Sutera Bar. No.25 Blok 38D, RT.003/RW.006, Panunggangan Tim., Kec. Pinang, Kota Tangerang Selatan, Banten 15143',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15143',
        //     'lat' => '-6.2326902',
        //     'lng' => '106.6645009',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'PT. MITRA AGRO SEMESTA',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);
        // // $company->branches()->create([
        // //     'name' => 'Tanjung Duren',
        // //     'address' => 'Tanjung Duren',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Kebon Jeruk',
        // //     'address' => 'Kebon Jeruk',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'SUN Alam Sutera',
        // //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        // //     'country' => 'Indonesia',
        // //     'province' => 'Banten',
        // //     'city' => 'Kota Tangerang Selatan',
        // //     'zip_code' => '15320',
        // //     'lat' => '-6.2348656',
        // //     'lng' => '106.5766001',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Kelapa Gading Timur',
        // //     'address' => 'Kelapa Gading Timur',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Kelapa Gading Barat',
        // //     'address' => 'Kelapa Gading Barat',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'STC Senayan',
        // //     'address' => 'STC Senayan',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Pluit',
        // //     'address' => 'Pluit',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Pondok Indah',
        // //     'address' => 'Pondok Indah',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Bali',
        // //     'address' => 'Bali',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Surabaya Timur',
        // //     'address' => 'Surabaya Timur',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Surabaya Barat',
        // //     'address' => 'Surabaya Barat',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Medan',
        // //     'address' => 'Medan',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Pekanbaru',
        // //     'address' => 'Pekanbaru',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Batam',
        // //     'address' => 'Batam',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Lampung',
        // //     'address' => 'Lampung',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Palembang',
        // //     'address' => 'Palembang',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Bandung',
        // //     'address' => 'Bandung',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Semarang',
        // //     'address' => 'Semarang',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Cirebon',
        // //     'address' => 'Cirebon',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Yogyakarta',
        // //     'address' => 'Yogyakarta',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Makassar',
        // //     'address' => 'Makassar',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Samarinda',
        // //     'address' => 'Samarinda',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Balikpapan',
        // //     'address' => 'Balikpapan',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Pontianak',
        // //     'address' => 'Pontianak',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Cibubur',
        // //     'address' => 'Cibubur',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Gading Serpong',
        // //     'address' => 'Gading Serpong',
        // //     'country' => 'Indonesia',
        // //     'province' => 'DKI Jakarta',
        // //     'city' => 'Jakarta Barat',
        // //     'zip_code' => '11610',
        // //     'lat' => '-6.1979899',
        // //     'lng' => '106.742916',
        // //     'umk' => 5067381,
        // // ]);

        // // $company = $group->companies()->create([
        // //     'name' => 'SUN ASA EDUCATION',
        // //     'address' => 'Sunway Geo Avenue, Sunway South Quay, Jalan Lagoon Selatan, Bandar Sunway, 47500 Subang Jaya, Selangor, Malaysia',
        // //     'country_id' => 1,
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // // ]);
        // // $company->positions()->createMany($positions);
        // // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // // $division->departments()->createMany($departments);
        // // $company->branches()->create([
        // //     'name' => 'Penang',
        // //     'address' => 'Penang',
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Kuala Lumpur',
        // //     'address' => 'Kuala Lumpur',
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Surabaya',
        // //     'address' => 'Surabaya',
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Petaling Jaya',
        // //     'address' => 'Petaling Jaya',
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // //     'umk' => 5067381,
        // // ]);
        // // $company->branches()->create([
        // //     'name' => 'Sunway',
        // //     'address' => 'Sunway',
        // //     'country' => 'Malaysia',
        // //     'province' => 'Selangor',
        // //     'city' => 'Subang Jaya',
        // //     'zip_code' => '47500',
        // //     'lat' => '3.0649282',
        // //     'lng' => '101.5256048',
        // //     'umk' => 5067381,
        // // ]);

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

        app(CompanyInitializeService::class)($company);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'Alba Alam Sutera',
        //     'address' => 'Ruko Alam Sutera 29D, Jl. Jalur Sutera No.28, Pakualam, Kec. Serpong Utara, Kota Tangerang Selatan, Banten 15320',
        //     'country' => 'Indonesia',
        //     'province' => 'Banten',
        //     'city' => 'Kota Tangerang Selatan',
        //     'zip_code' => '15320',
        //     'lat' => '-6.2348656',
        //     'lng' => '106.5766001',
        //     'umk' => 5067381,
        // ]);


        // GROUP PATROL
        // $group = Group::create([
        //     'name' => 'Group Patrol',
        // ]);

        // $company = $group->companies()->create([
        //     'name' => 'PT. Patroli 86',
        //     'address' => 'Jl. Patroli 86, Kebon Pala, Kec. Gambir, Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10110',
        //     'country_id' => 1,
        //     'country' => 'Indonesia',
        //     'province' => 'DKI Jakarta',
        //     'city' => 'Jakarta Barat',
        //     'zip_code' => '11610',
        //     'lat' => '-6.1979899',
        //     'lng' => '106.742916',
        // ]);
        // $company->positions()->createMany($positions);
        // $division = $company->divisions()->create(['name' => 'Operasional (' . $company->name . ')']);
        // $division->departments()->createMany($departments);
        // $company->branches()->create([
        //     'name' => 'Branch Patroli 1',
        //     'address' => 'Jl. Patroli 86, Kebon Pala, Kec. Gambir, Jakarta Pusat, Daerah Khusus Ibukota Jakarta 10110',
        //     'country' => 'Indonesia',
        //     'province' => 'DKI Jakarta',
        //     'city' => 'Jakarta Barat',
        //     'zip_code' => '11610',
        //     'lat' => '-6.1979899',
        //     'lng' => '106.742916',
        //     'umk' => 5067381,
        // ]);
    }
}
