<?php

namespace Database\Factories;

use App\Models\UserManual;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserManualFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserManual::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'serial_number' => $this->faker->unique()->ean8, // Or another unique identifier
            'description' => $this->faker->paragraph,
            'youtube_link' => 'https://www.youtube.com/watch?v=' . $this->faker->regexify('[a-zA-Z0-9_-]{11}'),
        ];
    }
}
