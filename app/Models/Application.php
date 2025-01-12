<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $guarded = [];

    protected $casts = [
        'apply_at' => 'datetime',
        'followup_at' => 'datetime',
        'followup_after_days' => 'integer',
        'followup_freq' => 'integer',
        'recruitor_reply' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
