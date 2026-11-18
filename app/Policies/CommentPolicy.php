<?php

namespace Canvas\Policies;

use App\Models\Comment;
use Canvas\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any comments.
     *
     * @param  \Canvas\Models\User|null  $user
     * @return mixed
     */
    public function viewAny(?User $user)
    {
        return true; // Anyone can view approved comments
    }

    /**
     * Determine whether the user can view the comment.
     *
     * @param  \Canvas\Models\User|null  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function view(?User $user, Comment $comment)
    {
        // Approved comments can be viewed by anyone
        if ($comment->is_approved) {
            return true;
        }

        // Unapproved comments can only be viewed by admins, editors, or the comment author
        if ($user) {
            return $user->is_admin || $user->is_editor || $comment->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create comments.
     *
     * @param  \Canvas\Models\User|null  $user
     * @return mixed
     */
    public function create(?User $user)
    {
        // Allow all visitors to create comments, including guests
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     *
     * @param  \Canvas\Models\User  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function update(User $user, Comment $comment)
    {
        // Only the comment author, admins, or editors can update a comment
        return $user->is_admin || $user->is_editor || $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can approve the comment.
     *
     * @param  \Canvas\Models\User  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function approve(User $user, Comment $comment)
    {
        // Only admins and editors can approve comments
        return $user->is_admin || $user->is_editor;
    }

    /**
     * Determine whether the user can delete the comment.
     *
     * @param  \Canvas\Models\User  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function delete(User $user, Comment $comment)
    {
        // Only the comment author, admins, or editors can delete a comment
        return $user->is_admin || $user->is_editor || $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the comment.
     *
     * @param  \Canvas\Models\User  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function restore(User $user, Comment $comment)
    {
        // Only admins or editors can restore comments
        return $user->is_admin || $user->is_editor;
    }

    /**
     * Determine whether the user can permanently delete the comment.
     *
     * @param  \Canvas\Models\User  $user
     * @param  \Canvas\Models\Comment  $comment
     * @return mixed
     */
    public function forceDelete(User $user, Comment $comment)
    {
        // Only admins can permanently delete comments
        return $user->is_admin;
    }
}