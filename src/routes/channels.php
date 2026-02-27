<?php

use App\Models\Reunion;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('reunion.{reunionId}', function ($user, $reunionId) {
    $reunion = Reunion::query()->find($reunionId);

    return $reunion && $user->can('view', $reunion);
});
