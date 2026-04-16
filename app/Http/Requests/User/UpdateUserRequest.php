<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->id : $routeUser;

        return [
            'username' => 'sometimes|string|max:50|',
            'email' => ['sometimes', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'password' => 'nullable|string|min:6|confirmed',
            'avatar' => 'nullable|file|image|max:5120',
            'role' => 'sometimes|in:admin,user',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ];
    }
}
