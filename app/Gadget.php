<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Gadget extends Model
{
    use SoftDeletes;
    //

    protected $fillable = [
        'Fa_Name', 'En_Name', 'Last_Value','status','Type'
    ];
}
