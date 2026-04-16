<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\ApiFormRequest;

class StorePostRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'caption' => 'nullable|string',
            'status' => 'sometimes|in:active,hidden',
            'images' => 'required|array|min:1',
            'images.*' => 'required|file|image|max:5120',
            'thumbnail_index' => 'nullable|integer|min:0',
        ];
    }
}
