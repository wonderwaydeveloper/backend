<?php

namespace Database\Factories;

use App\Models\{Conversation, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'message_type' => 'text',
            'content' => $this->faker->sentence(),
        ];
    }
}
