<?php

namespace App\Http\Controllers;

use App\Models\Store;

class StoreController extends Controller
{
    /**
     * List all store branches for the locator.
     */
    public function index()
    {
        return Store::orderBy('region')->orderBy('name')->get();
    }
}
