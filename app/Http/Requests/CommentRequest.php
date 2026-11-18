<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization will be handled through policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'post_id' => 'required|uuid|exists:canvas_posts,id',
            'content' => 'required|string|min:2|max:10000',
            'parent_id' => 'nullable|uuid|exists:canvas_comments,id',
        ];

        // Only require guest author information if user is not authenticated
        if (Auth::guest()) {
            $rules['author_name'] = 'required|string|max:255';
            $rules['author_email'] = 'required|email|max:255';
            $rules['author_url'] = 'nullable|url|max:255';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'post_id.required' => trans('canvas::app.validation.required', ['attribute' => 'post ID']),
            'post_id.exists' => trans('canvas::app.validation.exists', ['attribute' => 'post']),
            'content.required' => trans('canvas::app.validation.required', ['attribute' => 'comment content']),
            'author_name.required' => trans('canvas::app.validation.required', ['attribute' => 'name']),
            'author_email.required' => trans('canvas::app.validation.required', ['attribute' => 'email']),
            'author_email.email' => trans('canvas::app.validation.email', ['attribute' => 'email']),
            'author_url.url' => trans('canvas::app.validation.url', ['attribute' => 'website']),
        ];
    }
}