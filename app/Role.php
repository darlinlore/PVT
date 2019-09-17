<?php

namespace App;

use Laratrust\Models\LaratrustRole;

class Role extends LaratrustRole
{
  public $timestamps = true;
  public $guarded = ['id', 'correlative'];
  protected $fillable = ['module_id', 'name', 'action'];

  public function permissions()
  {
    return $this->belongsToMany(Permission::class, 'role_permissions');
  }
}