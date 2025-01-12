<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'skills' => 'array',          
        'experience' => 'float',      
        'notice_period' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
