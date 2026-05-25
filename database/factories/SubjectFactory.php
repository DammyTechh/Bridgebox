<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition()
    {
        $name = $this->faker->word();

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'description' => $this->faker->sentence(),
        ];
    }
}
