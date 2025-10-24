<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdScriptTask extends Model
{
    protected $fillable = [
        'reference_script',
        'outcome_description',
        'new_script',
        'analysis',
        'status',
        'error',
    ];
}
