<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdScriptTask extends Model
{
    use HasFactory;

    // NOTE: Keep only safe attributes here. If later you add privileged fields,
    // consider switching to $guarded = ['id'] and setting sensitive fields explicitly.
    protected $fillable = [
        'reference_script',
        'outcome_description',
        'new_script',
        'analysis',
        'status',
        'error_details',
    ];

    // TIP: If these text fields can grow large, ensure the migration uses TEXT/LONGTEXT.
    // Also think about FULLTEXT indexes if you plan to search in these columns.
    protected $casts = [
        'reference_script'    => 'string',
        'outcome_description' => 'string',
        'new_script'          => 'string',
        'analysis'            => 'string',
        'status'              => 'string',
        'error_details'       => 'string',
    ];

    // SUGGESTION: Consider using a PHP 8.1 backed Enum for status to avoid typos, e.g.:
    // enum TaskStatus: string { case Pending = 'pending'; case Completed = 'completed'; case Failed = 'failed'; }
    // and then cast: protected $casts = ['status' => TaskStatus::class];

}
