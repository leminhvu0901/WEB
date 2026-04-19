<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests; //hỗ trợ phân quyền/policy.
use Illuminate\Foundation\Validation\ValidatesRequests; //hỗ trợ validate dữ liệu trong controller.
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
