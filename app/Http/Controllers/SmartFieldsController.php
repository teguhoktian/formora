<?php

namespace App\Http\Controllers;

use App\Services\SmartFieldRegistry;
use Illuminate\Contracts\View\View;

class SmartFieldsController extends Controller
{
    public function __invoke(SmartFieldRegistry $registry): View
    {
        return view('smart-fields.index', [
            'definitions' => $registry->definitions(),
        ]);
    }
}
