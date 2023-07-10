<?php

namespace Database\Factories;

use App\Models\Code;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Code::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => Str::random(16),
            'campaign_id' => random_int(1,10),
            'value' => rand(20000, 500000),
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'status' => random_int(1, 4),
            'created_by' => random_int(1,10),
            'updated_by' => random_int(1,10),
        ];
    }
}
