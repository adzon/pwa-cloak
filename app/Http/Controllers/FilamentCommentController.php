<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;

class FilamentCommentController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:comments,id',
            'nickname' => 'required|string|max:20',
            'content' => 'required|string|min:5|max:500',
            'language_id' => 'required|exists:languages,id',
        ]);

        Comment::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json([
            'success' => true,
            'comments' => Comment::with('language')->get(),
        ]);
    }

    public function delete($id)
    {
        Comment::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'comments' => Comment::with('language')->get(),
        ]);
    }
}
