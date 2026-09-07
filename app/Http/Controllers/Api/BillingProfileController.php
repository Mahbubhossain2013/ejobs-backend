<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingProfileController extends Controller
{
    /**
     * Get the authenticated user's billing profile.
     * Auto-creates an empty profile if one doesn't exist yet.
     */
    public function getProfile()
    {
        $profile = BillingProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'company_name'   => '',
                'vat_number'     => '',
                'billing_name'   => '',
                'address_line_1' => '',
                'address_line_2' => '',
                'city'           => '',
                'state'          => '',
                'postal_code'    => '',
                'country_code'   => '',
                'phone'          => '',
                'email'          => Auth::user()->email ?? '',
                'is_business'    => false,
                'is_default'     => true,
            ]
        );

        return response()->json(['status' => true, 'data' => $profile]);
    }

    /**
     * Create or update the authenticated user's billing profile.
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'nullable|string|max:255',
            'vat_number'     => 'nullable|string|max:255',
            'billing_name'   => 'nullable|string|max:255',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'nullable|string|max:255',
            'state'          => 'nullable|string|max:255',
            'postal_code'    => 'nullable|string|max:50',
            'country_code'   => 'nullable|string|max:10',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:255',
            'is_business'    => 'nullable|boolean',
        ]);

        $profile = BillingProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return response()->json([
            'status'  => true,
            'message' => 'Billing profile updated successfully.',
            'data'    => $profile,
        ]);
    }
}
