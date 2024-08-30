<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::where('name', 'like', '%patroli%')->firstOrFail();

        for ($i = 1; $i < 4; $i++) {
            $client = Client::create([
                'company_id' => $company->id,
                'name' => sprintf("Client %s %s", $i, $company->name),
                'phone' => fake('id_ID')->phoneNumber(),
                'address' => fake('id_ID')->address(),
            ]);

            $patrol = $client->patrols()->create([
                'name' => sprintf("Patrol %s %s", $i, $client->name),
                'lat' => fake('id_ID')->latitude(),
                'lng' => fake('id_ID')->longitude(),
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'start_time' => "07:00:00",
                'end_time' => "17:00:00",
                'description' => sprintf("Description patrol %s %s", $i, $client->name),
            ]);

            for ($i = 1; $i < 4; $i++) {
                $clientLocation = $client->clientLocations()->create([
                    'name' => sprintf("Location %s %s", $i, $client->name),
                    'lat' => fake('id_ID')->latitude(),
                    'lng' => fake('id_ID')->longitude(),
                    'description' => fake('id_ID')->text(),
                ]);

                $patrolLocation = $patrol->patrolLocations()->create([
                    'client_location_id' => $clientLocation->id,
                ]);

                for ($i = 1; $i < 4; $i++) {
                    $patrolLocation->tasks()->create([
                        'name' => sprintf("Task %s", $i),
                        'description' => fake('id_ID')->text(),
                    ]);
                }
            }
        }
    }
}
