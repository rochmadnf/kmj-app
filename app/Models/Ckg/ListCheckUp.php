<?php

namespace App\Models\Ckg;

use Illuminate\Database\Eloquent\Model;

class ListCheckUp extends Model
{
    protected $table = 'ckg_list_check_ups';

    protected $fillable = ['group_name', 'group_code', 'label', 'code', 'school_category'];
}
