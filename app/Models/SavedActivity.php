<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedActivity extends Model
{
    use HasFactory;

    protected $table = 'saved_activities';

    protected $fillable = [
        'user_id',
        'activity_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'saved_activities');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'saved_activities');
    }
}
