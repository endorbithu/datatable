<?php

namespace DelocalZrt\Datatable\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatatableAttrSchoolDegree extends Model
{
    use HasFactory;

    public $timestamps = true;

    public function datatableUsers()
    {
        return $this->hasMany(DatatableUser::class);
    }

}
