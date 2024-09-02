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
                'phone' => rand(1111111111, 9999999999),
                'address' => 'Address ' . $i,
            ]);

            $patrol = $client->patrols()->create([
                'name' => sprintf("Patrol %s %s", $i, $client->name),
                'lat' => rand(1111111111, 9999999999),
                'lng' => rand(1111111111, 9999999999),
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'start_time' => "07:00:00",
                'end_time' => "17:00:00",
                'description' => sprintf("Description patrol %s %s", $i, $client->name),
            ]);

            for ($i = 1; $i < 4; $i++) {
                $clientLocation = $client->clientLocations()->create([
                    'name' => sprintf("Location %s %s", $i, $client->name),
                    'lat' => rand(1111111111, 9999999999),
                    'lng' => rand(1111111111, 9999999999),
                    'description' => 'description ' . $i,
                ]);

                $patrolLocation = $patrol->patrolLocations()->create([
                    'client_location_id' => $clientLocation->id,
                ]);

                for ($i = 1; $i < 4; $i++) {
                    $patrolLocation->tasks()->create([
                        'name' => sprintf("Task %s", $i),
                        'description' => 'description ' . $i,
                    ]);
                }
            }
        }
    }
}
