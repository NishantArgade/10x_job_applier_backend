<?php

namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    const ADMIN = 'Admin';
    const CLIENT = 'Client';
}
