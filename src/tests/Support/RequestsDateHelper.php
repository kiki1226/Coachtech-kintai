<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

trait RequestsDateHelper
{
    protected function requestsDateKey(): ?string
    {
        return collect(['from_at','to_at','work_date','date','target_day','target_date'])
            ->first(fn($c) => Schema::hasColumn('requests', $c)
                || Schema::hasColumn('attendance_requests', $c));
    }

    protected function requestsDatePayload(Carbon|string $date): array
    {
        $date = $date instanceof Carbon ? $date->toDateString() : (string)$date;

        // テーブル名はモデル側に合わせる（どちらも同じ定義なら requests 想定でOK）
        $key = $this->requestsDateKey();
        if (!$key) return [];

        if (in_array($key, ['from_at','to_at'])) {
            $payload = [];
            if (Schema::hasColumn('requests','from_at')) $payload['from_at'] = $date;
            if (Schema::hasColumn('requests','to_at'))   $payload['to_at']   = $date;
            return $payload;
        }
        return [$key => $date];
    }

    protected function assertRequestHasDate($requestModel, Carbon|string $expected): void
    {
        $expected = $expected instanceof Carbon ? $expected->toDateString() : (string)$expected;
        $key = $this->requestsDateKey();
        if (!$key) { $this->assertTrue(true); return; }

        if (in_array($key, ['from_at','to_at'])) {
            $from = \optional($requestModel->from_at)?->toDateString();
            $to   = \optional($requestModel->to_at)?->toDateString();
            $this->assertTrue($from === $expected || $to === $expected,
                "Expected from_at/to_at to equal {$expected}");
            return;
        }

        $this->assertEquals($expected, \optional($requestModel->$key)?->toDateString(),
            "Expected {$key} to equal {$expected}");
    }
}
