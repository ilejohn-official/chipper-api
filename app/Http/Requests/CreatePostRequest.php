<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user();
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif,webp|max:5120|dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
        ];
    }
}
