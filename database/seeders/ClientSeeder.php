<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $company = Company::where('name', 'like', '%patroli%')->firstOrFail();

        for ($i = 1; $i < 4; $i++) {
            $client = Client::create([
                'company_id' => $company->id,
                'name' => sprintf("Client %s %s", $i, $company->name),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
            ]);

            $patrol = $client->patrols()->create([
                'name' => sprintf("Patrol %s %s", $i, $client->name),
                'lat' => $faker->latitude(),
                'lng' => $faker->longitude(),
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'start_time' => "07:00:00",
                'end_time' => "17:00:00",
                'description' => sprintf("Description patrol %s %s", $i, $client->name),
            ]);

            for ($i = 1; $i < 4; $i++) {
                $clientLocation = $client->clientLocations()->create([
                    'name' => sprintf("Location %s %s", $i, $client->name),
                    'lat' => $faker->latitude(),
                    'lng' => $faker->longitude(),
                    'description' => $faker->text(),
                ]);

                $patrolLocation = $patrol->patrolLocations()->create([
                    'client_location_id' => $clientLocation->id,
                ]);

                for ($i = 1; $i < 4; $i++) {
                    $patrolLocation->tasks()->create([
                        'name' => sprintf("Task %s", $i),
                        'description' => $faker->text(),
                    ]);
                }
            }
        }
    }
}
