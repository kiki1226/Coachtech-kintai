<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'attendance_id',
        'user_id',
        'type',
        'status',
        'reason',
        'target_date',
        'from_at', 
        'to_at',
        'changes', 
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'changes'     => 'array', 
        'target_date' => 'date',
        'approved_at' => 'datetime',
        'from_at'     => 'datetime',
        'to_at'       => 'datetime',
    ];

    // type の許可値（DBに合わせる）
    public const TYPE_ATTENDANCE_CORRECTION = 'attendance_correction';
    public const TYPE_LEAVE                 = 'leave';
    public const TYPE_OVERTIME              = 'overtime';

    // status もDBに合わせる（必要に応じて）
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELED = 'canceled';

    // ラベル対応
    public const STATUS_LABELS = [
        'pending'  => '承認待ち',
        'approved' => '承認済み',
        'rejected' => '却下済み',   // ← これを追加
        'canceled' => '取消',
    ];
    // リレーション
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // アクセサ：$model->status_label で使えるように
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function attendance()
    {
        return $this->belongsTo(\App\Models\Attendance::class);
    }

}