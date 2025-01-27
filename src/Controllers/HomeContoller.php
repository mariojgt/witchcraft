<?php

namespace Mariojgt\Witchcraft\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mariojgt\Witchcraft\Services\WitchcraftTrigger;

class HomeContoller extends Controller
{
    public function index()
    {
        // Render the witchcraft view
        return view('witchcraft::content.index');
    }
}
