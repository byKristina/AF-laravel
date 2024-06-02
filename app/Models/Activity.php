<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'time',
        'address',
        'gender',
        'location_id',
        'activity_type_id',
        'organizer_id',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }


    public function applications()
    {
        return $this->belongsToMany(User::class, 'applications', 'activity_id', 'user_id')->withPivot('status');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class); 
    }
    
}
