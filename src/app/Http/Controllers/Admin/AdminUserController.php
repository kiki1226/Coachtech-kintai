<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCreateRequest;
use App\Http\Requests\Admin\AdminPromoteRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('is_admin', true)->orderBy('id')->get();
        return view('admin.admins.index', compact('admins'));
    }

    // 新規に管理者ユーザーを作成
    public function store(AdminCreateRequest $request)
    {
        $validated = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','string','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8'],
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => true,
        ]);

        return back()->with('success','管理者を作成しました。');
    }

   /** 既存ユーザーを昇格 */
    public function promote(Request $request)
    {
        $request->validate(['email' => ['required','email']]);
        $user = User::where('email', $request->email)->firstOrFail();
        $user->is_admin = true;
        $user->save();

        return back()->with('success', '管理者に昇格しました。');
    }

    /** 降格 */
    public function demote(User $user)
    {
        $user->is_admin = false;
        $user->save();

        return back()->with('success', '管理者権限を外しました。');
    }

    /** 削除 */
    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', '管理者ユーザーを削除しました。');
    }

    /** スタッフ一覧（一般ユーザーを表示） */
    public function staffIndex(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            // is_admin = 0 or NULL を“スタッフ”扱い
            ->where(function ($q) {
                $q->where('is_admin', false)->orWhereNull('is_admin');
            })
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'q'));
    }

    /** 管理者ユーザー管理（今までのページ） */
    public function adminIndex()
    {
        $admins = User::where('is_admin', true)->orderBy('id')->get();
        return view('admin.admins.index', compact('admins'));
    }
}
