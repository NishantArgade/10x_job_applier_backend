<?php

namespace App\Http\Data;

use Spatie\LaravelData\Data;

class JobApplicationData extends Data
{
    public function __construct(
        public string $email,
        public string $body,
        public ?string $subject = null,
        public ?string $attachment = null,
    ) {
    }
}
