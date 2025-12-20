<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        $user->loadCount('posts', 'followers', 'following');

        return response()->json($user);
    }

    public function posts(User $user)
    {
        $posts = $user->posts()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->latest()
            ->paginate(20);

        return response()->json($posts);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:500',
            'avatar' => 'sometimes|nullable|string|url|max:255',
        ]);

        $data = $request->only(['name', 'bio']);
        
        if ($request->has('avatar')) {
            $data['avatar'] = $request->avatar;
        }

        $user->update($data);

        return response()->json($user);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->select('id', 'name', 'username', 'avatar')
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'is_private' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->update(['is_private' => $request->is_private]);

        return response()->json([
            'message' => 'Privacy settings updated',
            'is_private' => $user->is_private,
        ]);
    }
}
