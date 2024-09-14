<?php

namespace Endorbit\Datatable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatatableUser extends Model
{
    use HasFactory;

    public $timestamps = true;


    public function datatableAttrSchoolDegree()
    {
        return $this->belongsTo(DatatableAttrSchoolDegree::class);
    }

    //return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    public function datatableAttrJobCategories()
    {
        return $this->belongsToMany(DatatableAttrJobCategory::class);
    }

}
