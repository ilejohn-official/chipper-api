<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FavoriteUserRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->isNot($this->route('user'));
    }

    public function rules()
    {
        return [];
    }
}
