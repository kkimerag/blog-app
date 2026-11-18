<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Canvas\Models\Post;
use App\Http\Requests\CommentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    /**
     * Display a listing of comments.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // dd($request);
        $comments = Comment::with(['user', 'replies.user'])
            ->when($request->has('post_id'), function ($query) use ($request) {
                return $query->where('post_id', $request->post_id);
            })
            ->when($request->has('pending'), function ($query) {
                return $query->pending();
            })
            ->when($request->has('approved'), function ($query) {
                return $query->approved();
            })
            ->when($request->has('root_only'), function ($query) {
                return $query->root();
            })
            ->latest()
            ->paginate();

        return response()->json($comments);
    }

    /**
     * Store a newly created comment.
     *
     * @param  CommentRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CommentRequest $request)
    {
        $post = Post::findOrFail($request->post_id);
        
        $data = $request->validated();
        
        // Set author information for guest comments
        if (Auth::guest()) {
            $data['author_ip'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
        } else {
            $data['user_id'] = Auth::id();
            
            // Auto-approve comments from authenticated users based on role
            if (Auth::user()->getIsAdminAttribute() || Auth::user()->getIsEditorAttribute()) {
                $data['approved_at'] = now();
            }
        }

        $comment = new Comment($data);
        $post->comments()->save($comment);

        return response()->json($comment->load(['user', 'post']), 201);
    }

    /**
     * Display the specified comment.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $comment = Comment::with(['user', 'post', 'replies.user'])->findOrFail($id);

        return response()->json($comment);
    }

    /**
     * Update the specified comment.
     *
     * @param  CommentRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CommentRequest $request, $id)
    {
        $comment = Comment::findOrFail($id);
        
        $this->authorize('update', $comment);
        
        $comment->update($request->validated());

        return response()->json($comment->fresh(['user', 'post']));
    }

    /**
     * Approve the specified comment.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        $comment = Comment::findOrFail($id);
        
        $this->authorize('approve', $comment);
        
        $comment->update(['approved_at' => now()]);

        return response()->json($comment->fresh());
    }

    /**
     * Remove the specified comment from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        
        $this->authorize('delete', $comment);
        
        $comment->delete();

        return response()->json(null, 204);
    }
}