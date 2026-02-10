<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Inertia\Inertia;

class QrGeneratorController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/QrGenerator/Index', [
            'bankConfig' => [
                'bank_code' => Setting::getValue('bank_code', 'MB'),
                'account_number' => Setting::getValue('bank_account_number', ''),
                'account_name' => Setting::getValue('bank_account_name', ''),
            ],
        ]);
    }
}
