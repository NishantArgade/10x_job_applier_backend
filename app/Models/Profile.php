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

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

      // Active Template
      public function activeTemplate()
      {
          return $this->belongsTo(Template::class, 'template_id');
      }
  
      // Active Resume
      public function activeResume()
      {
          return $this->belongsTo(Resume::class, 'resume_id');
      }

}
