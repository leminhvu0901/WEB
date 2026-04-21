<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\ApiFormRequest;

// Request validate du lieu khi tao bai viet moi.
class StorePostRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            // Noi dung bai viet, co the bo trong.
            'caption' => 'nullable|string',
            // Trang thai bai viet, neu gui len thi chi duoc active hoac hidden.
            'status' => 'sometimes|in:active,hidden',
            // Ho tro ca schema moi (images[]) va schema cu (image).
            'images' => 'required_without:image|array|min:1',
            // Tung phan tu trong images phai la file anh, toi da 5MB/anh.
            'images.*' => 'required_with:images|file|image|max:5120',
            // Ho tro upload 1 anh don le cho schema cu.
            'image' => 'required_without:images|file|image|max:5120',
            // Vi tri anh dai dien (thumbnail), co the khong gui.
            'thumbnail_index' => 'nullable|integer|min:0',
        ];
    }
}
