<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'password' => \Hash::make('12345678'),
            'security_code' => \Hash::make('123456'),
            'mobile' => $this->faker->phoneNumber,
            'username' => $this->faker->name,
            'group_id' => 'default',
            'nationality' => 'TW',
            'authentication_status' => 'passed',
            'is_tester' => true,
        ];
    }
}
