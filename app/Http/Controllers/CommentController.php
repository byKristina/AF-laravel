<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['getCommentsByActivityId']]);
    }
    public function getCommentsByActivityId($id)
    {

        $comments = Comment::where('activity_id', $id)->with('user')->get();
        return response()->json($comments, 200);
    }

    public function createComment(Request $request)
    {

        try {
            $data = $request->validate([
                'activity_id' => 'required|integer',
                'content' => 'required|string|min:3|max:255',
            ]);

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'activity_id' => $data['activity_id'],
                'content' => $data['content'],
            ]);

            return response()->json(['message' => 'Comment created successfully', 'comment' => $comment], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create comment', 'errors' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $comment = Comment::find($id);
            $activityOrganizer = $comment->activity->organizer_id;
          
            if (
                auth()->user()->role_id === 1 || 
                auth()->user()->id === $activityOrganizer || 
                auth()->user()->id === $comment->user_id
            ) {
                $comment->delete();
                return response()->json(null, 204); 
            }

            return response()->json(['error' => 'Unauthorized deletion attempt'], 403);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete comment', 'errors' => $e->getMessage()], 500);
        }
    }
}
