<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    // 必要に応じて $fillable などは既存のまま

    // ステータス定義（文字列でも int でもOK）
    public const STATUS_PENDING  = 'pending';   // 承認待ち
    public const STATUS_APPROVED = 'approved';  // 承認済み
    public const STATUS_REJECTED = 'rejected';  // 却下（あれば）

    /** 承認待ちだけ */
    public function scopePending($q)
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    /** 承認済みだけ */
    public function scopeApproved($q)
    {
        return $q->where('status', self::STATUS_APPROVED);
    }

    /** ラベル（Blade で使いやすく） */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            self::STATUS_REJECTED => '却下',
            default               => '—',
        };
    }

    /** バッジ色（お好み） */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'badge badge-pending',
            self::STATUS_APPROVED => 'badge badge-approved',
            self::STATUS_REJECTED => 'badge badge-rejected',
            default               => 'badge',
        };
    }
}
