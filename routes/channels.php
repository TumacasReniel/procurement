<?php

use App\Models\Procurement;
use App\Models\ProcurementApp;
use App\Models\ProcurementPpmp;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('procurement.{id}', function ($user, $id) {
    return $user && Procurement::query()->whereKey($id)->exists();
});

Broadcast::channel('procurement-requests', function ($user) {
    return (bool) $user;
});

Broadcast::channel('procurement-plans', function ($user) {
    return (bool) $user;
});

Broadcast::channel('procurement-plan.{id}', function ($user, $id) {
    return $user && ProcurementPpmp::query()->whereKey($id)->exists();
});

Broadcast::channel('procurement-plan-app.{id}', function ($user, $id) {
    return $user && ProcurementApp::query()->whereKey($id)->exists();
});
