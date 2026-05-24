<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRoleAccessBlock extends Model
{
    protected $fillable = [
        'etablissement_id',
        'role',
        'menu_key',
        'created_by',
        'updated_by',
    ];
}
