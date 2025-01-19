<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable;


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isClient()
    {
        return $this->hasRole(Role::CLIENT);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
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

    public function permissionNames()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    public function getRedirectURL(string|int $pathOrPageCode = null): int|string
    {
        $pageUrls = $this->getFrontendUrlsMapping();

        if ($pathOrPageCode) {
            return $pageUrls[$pathOrPageCode] ?? $pathOrPageCode;
        }

        if ($this->isAdmin()) {
            return $pageUrls[$pageUrls[2]];
        }

        return $pageUrls[$pageUrls[1]];
    }

    public function getFrontendUrlsMapping()
    {
        return [
            1 => '/dashbard',
            2 => '/dashbard/manage',
            3 => '/applications',
            4 => '/templates',
            5 => '/resumes',
            6 => '/profile',
            7 => '/settings',
            8 => '/logout',
        ];
    }
}
