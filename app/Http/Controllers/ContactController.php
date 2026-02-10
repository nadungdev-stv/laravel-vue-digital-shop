<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; // Future use

use App\Models\Setting;

class ContactController extends Controller
{
    public function index()
    {
        // Fetch settings if available, otherwise defaults
        $siteName = config('app.name', 'Veyrix');
        $contactEmail = Setting::getValue('contact_email', 'admin@veyrix.pro');
        $contactPhone = Setting::getValue('contact_phone', '0848877758');
        $contactWorkHours = Setting::getValue('contact_work_hours', 'Thứ 2 - Chủ nhật: 8:00 - 22:00');
        $socialFacebook = Setting::getValue('social_facebook', '#');
        $socialTelegram = Setting::getValue('social_telegram', '#');
        $socialZalo = Setting::getValue('social_zalo', '#');

        return Inertia::render('Contact', [
            'siteName' => $siteName,
            'contactEmail' => $contactEmail,
            'contactPhone' => $contactPhone,
            'contactWorkHours' => $contactWorkHours,
            'socialFacebook' => $socialFacebook,
            'socialTelegram' => $socialTelegram,
            'socialZalo' => $socialZalo,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Logic to store ticket or send email
        // For now, just insert into support_tickets table if it exists, or just log it
        try {
            // Check if user is logged in
            $userId = auth()->id();

            DB::table('support_tickets')->insert([
                'user_id' => $userId,
                'name' => $validated['name'], // If table supports guest names
                'email' => $validated['email'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'status' => 'open', // Default status
                'priority' => 'medium',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If table doesn't exist or other error, just log
            // \Log::error('Contact form error: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.');
    }
}
