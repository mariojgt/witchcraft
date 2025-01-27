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
        // This will automatically trigger any diagrams watching User creation
        // $user = User::create([
        //     'name' => 'testemario',
        //     // random email
        //     'email' => Str::random(10) . '@gmail.com',
        //     'password' => 'password',
        // ]);
        // dd('here');
        // Example usage in a controller or service
        // WitchcraftTrigger::execute(8, ['status' => 'active']);
        // dd('here');
        // Render the witchcraft view
        return view('witchcraft::content.index');
    }
}
