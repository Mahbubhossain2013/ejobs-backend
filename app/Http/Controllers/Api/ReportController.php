<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Submit a new report (job, company, user, etc.)
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'reportable_type' => 'required|string|in:job,company,user',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|in:spam,scam,inappropriate,fake,duplicate,other',
            'description' => 'nullable|string|max:1000',
        ]);

        // Map short type to full class
        $typeMap = [
            'job' => \App\Models\Job::class,
            'company' => \App\Models\Company::class,
            'user' => \App\Models\User::class,
        ];

        $reportableType = $typeMap[$request->reportable_type];

        // Check if the reportable exists
        if (!$reportableType::find($request->reportable_id)) {
            return response()->json(['status' => false, 'message' => 'Reportable not found'], 404);
        }

        // Check for duplicate report
        $existing = Report::where('reporter_id', $user->id)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $request->reportable_id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json(['status' => false, 'message' => 'You already have a pending report for this item']);
        }

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $request->reportable_id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Report submitted successfully. Our team will review it.',
        ]);
    }
}
