<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRequest;
use App\Models\Attendance;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $tab    = $request->query('tab', 'pending'); // 'pending' | 'approved' | 'rejected'

        // 本人の出勤記録に紐づく申請のみ
        $base = AttendanceRequest::query()
            ->with(['attendance:id,user_id,work_date'])
            ->whereHas('attendance', fn($q) => $q->where('user_id', $userId));

        $requests = (clone $base)
            ->when($tab === 'pending',  fn($q) => $q->where('status', 'pending'))
            ->when($tab === 'approved', fn($q) => $q->where('status', 'approved'))
            ->when($tab === 'rejected', fn($q) => $q->where('status', 'rejected'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('requests.index', [
            'requests'      => $requests,
            'tab'           => $tab,
            'countPending'  => (clone $base)->where('status', 'pending')->count(),
            'countApproved' => (clone $base)->where('status', 'approved')->count(),
            'countRejected' => (clone $base)->where('status', 'rejected')->count(),
        ]);
    }

    public function show(Request $request, AttendanceRequest $requestItem)
    {
        AttendanceRequest::create([
            'user_id'       => $request->user()->id,
            'attendance_id' => $attendance->id ?? null,
            'type'          => 'attendance_correction',
            'status'        => 'pending',
            'target_date'   => $request->input('date'), // 予備
            'reason'        => $request->input('reason'),
            'changes'       => [
                'clock_in_at'       => $request->input('clock_in_at'),   // "2025-09-05 09:00"
                'clock_out_at'      => $request->input('clock_out_at'),  // "2025-09-05 18:00"
                'break_started_at'  => $request->input('break_started_at'),
                'break_ended_at'    => $request->input('break_ended_at'),
                // 未入力は送らない or null を入れる
            ],
        ]);
        // 所有者チェック（申請に勤怠が紐づいていない場合は 404 か 403 にしたいならここで調整）
        abort_unless(
            optional($requestItem->attendance)->user_id === $request->user()->id,
            403
        );

        // 勤怠に紐づいているなら勤怠詳細へリダイレクト
        if ($requestItem->attendance) {
            return redirect()->route('attendance.show', [
                'attendance' => $requestItem->attendance->id,
            ]);
        }

        // 勤怠未紐づけの申請（例: 休暇申請など）はそのまま申請詳細へ
        return view('requests.show', compact('requestItem'));
    }

    public function create(string $type)
    {
        abort_unless(in_array($type, ['edit', 'leave'], true), 404);
        return view('requests.create', compact('type'));
    }

    public function store(Request $request, string $type)
    {
        $request->validate([
            'date'   => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        // TODO: 保存実装
        return redirect()->route('requests.index')
            ->with('success', '申請を受け付けました（ダミー）。');
    }
}
