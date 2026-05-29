<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->where('role', $request->role));

        $perPage = min((int)($request->per_page ?? 15), 100);
        return response()->json($query->latest()->paginate($perPage));
    }

    public function optometrists()
    {
        return response()->json(User::where('role', 'optometrist')->select('id', 'name')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:admin,receptionist,optometrist,inventory_staff',
            'password' => 'required|min:8|confirmed',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => "sometimes|email|unique:users,email,{$user->id}",
            'phone'     => 'nullable|string|max:20',
            'role'      => 'sometimes|in:admin,receptionist,optometrist,inventory_staff',
            'is_active' => 'sometimes|boolean',
            'password'  => 'nullable|min:8|confirmed',
        ]);

        // Prevent demoting the last admin
        if (isset($validated['role']) && $validated['role'] !== 'admin' && $user->role === 'admin') {
            if (User::where('role', 'admin')->where('id', '!=', $user->id)->count() === 0) {
                return response()->json(['message' => 'Cannot demote the last admin account.'], 422);
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json($user->fresh());
    }

    public function destroy(User $user, Request $request)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }
        if ($user->role === 'admin' && User::where('role', 'admin')->where('id', '!=', $user->id)->count() === 0) {
            return response()->json(['message' => 'Cannot delete the last admin account.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }
}
