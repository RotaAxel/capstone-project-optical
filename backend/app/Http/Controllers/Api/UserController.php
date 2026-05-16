<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::latest()->paginate(15));
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

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json($user->fresh());
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }
}
