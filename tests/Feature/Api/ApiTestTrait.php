<?php

namespace Tests\Feature\Api;

use App\Models\User;

trait ApiTestTrait
{
    public function link(string $path)
    {
        return env('API_URL', 'http://localhost:8000')."/$path";
    }

    public function apiAs($method, $uri, array $data = [], array $headers = [], $user = null)
    {
        $user = $user ? $user : User::factory()->make();

        $headers = array_merge([
            'Authorization' => 'Bearer '.\JWTAuth::fromUser($user),
        ], $headers);

        return $this->api($method, $uri, $data, $headers);
    }


    public function api($method, $uri, array $data = [], array $headers = [])
    {
        return $this->json($method, $uri, $data, $headers);
    }
}
