<?php
namespace Mariojgt\Witchcraft\Controllers;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Show the witchcraft editor interface
     */
    public function index()
    {
        return view('witchcraft::content.index');
    }
}
