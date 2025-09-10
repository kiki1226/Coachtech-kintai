<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','email','password','is_admin']; 

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',                                   
    ];

    // リレーション例（必要なら）
    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    // もし申請モデルを使うなら（Illuminate\Http\Request と名前衝突を避けるため RequestModel 推奨）
    public function requests()
    {
        return $this->hasMany(RequestModel::class);
    }

}
