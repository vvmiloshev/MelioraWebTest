<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdScriptTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_script',
        'outcome_description',
        'new_script',
        'analysis',
        'status',
        'error_details',
    ];

    protected $casts = [
        'reference_script'    => 'string',
        'outcome_description' => 'string',
        'new_script'          => 'string',
        'analysis'            => 'string',
        'status'              => 'string',
        'error_details'       => 'string',
    ];
}
