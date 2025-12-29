<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\VerifyBulkEmailsJob;
use App\Jobs\VerifyEmailJob;
use App\Models\VerificationResult;

class VerificationController extends Controller
{
    public function verifySingle(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        if (!Auth::user()->hasCredits()) {
            return response()->json(['error' => 'Insufficient credits'], 402);
        }

        VerifyEmailJob::dispatch($email);
        Auth::user()->deductCredits(1);

        return response()->json(['message' => 'Verification queued']);
    }

    public function verifyBulk(Request $request)
    {
        $request->validate(['emails' => 'required|array']);
        $emails = $request->input('emails');

        if (!Auth::user()->hasCredits(count($emails))) {
            return response()->json(['error' => 'Insufficient credits'], 402);
        }

        $job = VerifyBulkEmailsJob::dispatch($emails);
        Auth::user()->deductCredits(count($emails));

        return response()->json(['job_id' => $job->job ?? null]);
    }

    public function verifyFile(Request $request)
    {
        $request->validate(['file' => 'required|file']);
        $path = $request->file('file')->getRealPath();

        $emails = array_filter(array_map('trim', file($path)));
        VerifyBulkEmailsJob::dispatch($emails);

        return response()->json(['message' => 'File queued for verification']);
    }

    public function status($jobId)
    {
        // Placeholder: return job status
        return response()->json(['job_id' => $jobId, 'status' => 'processing']);
    }

    public function results($jobId)
    {
        $results = VerificationResult::where('job_id', $jobId)->get();
        return response()->json($results);
    }

    public function export($jobId)
    {
        // Export to CSV or trigger Google Sheets export
        return response()->json(['message' => 'Export queued']);
    }
}
