<?php

namespace DelocalZrt\Datatable\Models;
use App\Models\AttrJobCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatatableAttrJobCategory extends Model
{
    use HasFactory;
    public $timestamps = true;


    public function users()
    {
        return $this->belongsToMany(DatatableUser::class);
    }

    public function parent()
    {
        return $this->belongsTo(DatatableAttrJobCategory::class, 'parent_id');
    }
}
