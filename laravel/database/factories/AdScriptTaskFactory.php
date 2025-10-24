<?php

namespace Database\Factories;

use App\Models\AdScriptTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdScriptTask> */
class AdScriptTaskFactory extends Factory
{
    protected $model = AdScriptTask::class;

    public function definition(): array
    {
        return [
            'reference_script'    => $this->faker->paragraph(),
            'outcome_description' => $this->faker->sentence(),
            'new_script'          => null,
            'analysis'            => null,
            'status'              => 'pending',
            'error_details'       => null,
        ];
    }
}
