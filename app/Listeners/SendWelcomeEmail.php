<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(UserRegistered $event)
    {
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}
