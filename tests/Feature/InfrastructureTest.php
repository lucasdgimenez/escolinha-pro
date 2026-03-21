<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

it('application boots successfully', function () {
    $this->get('/')->assertStatus(200);
});

it('health check route returns 200', function () {
    $this->getJson('/healthz')
        ->assertStatus(200)
        ->assertJson(['status' => 'ok']);
});

it('database connection works', function () {
    $result = DB::select('SELECT 1 as result');

    expect($result)->not->toBeEmpty()
        ->and($result[0]->result)->toBe(1);
});

it('redis connection works', function () {
    $pong = Redis::connection()->command('PING');

    expect($pong)->toBeTrue();
});
