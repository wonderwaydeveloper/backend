<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Authentication Routes
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting authentication
| routes for your application. These routes are loaded by the
| BroadcastServiceProvider and all of them will be assigned to the
| "web" middleware group. Make something great!
|
*/

Broadcast::routes(['middleware' => ['auth:sanctum']]);
