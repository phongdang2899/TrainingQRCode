<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => random_int(1, 4),
            'created_by' => random_int(1, 10),
            'updated_by' => random_int(1, 10),
        ];
    }
}
