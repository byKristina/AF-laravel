<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'user_id',
        'activity_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'applications');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'applications');
    }
}
