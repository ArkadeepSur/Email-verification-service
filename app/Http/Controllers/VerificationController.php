<?php

namespace App\Http\Controllers;

use App\Jobs\VerifyBulkEmailsJob;
use App\Jobs\VerifyEmailJob;
use App\Models\VerificationResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function verifySingle(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        if (! Auth::user()->hasCredits()) {
            return response()->json(['error' => 'Insufficient credits'], 402);
        }

        VerifyEmailJob::dispatch(Auth::user()->id, $email);
        Auth::user()->deductCredits(1);

        return response()->json(['message' => 'Verification queued']);
    }

    public function verifyBulk(Request $request)
    {
        $request->validate(['emails' => 'required|array']);
        $emails = $request->input('emails');

        if (! Auth::user()->hasCredits(count($emails))) {
            return response()->json(['error' => 'Insufficient credits'], 402);
        }

        $job = VerifyBulkEmailsJob::dispatch(Auth::user()->id, $emails);
        Auth::user()->deductCredits(count($emails));

        return response()->json(['job_id' => $job->job ?? null]);
    }

    public function verifyFile(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,txt']);
        $path = $request->file('file')->getRealPath();

        $emails = array_filter(array_map('trim', file($path)));
        
        if (! Auth::user()->hasCredits(count($emails))) {
            return response()->json(['error' => 'Insufficient credits'], 402);
        }

        $job = VerifyBulkEmailsJob::dispatch(Auth::user()->id, $emails);
        Auth::user()->deductCredits(count($emails));

        return response()->json(['job_id' => $job->job ?? null], 202);
    }

    public function status($jobId)
    {
        // For now, return processing status
        // In production, would query job queue system
        return response()->json(['job_id' => $jobId, 'status' => 'processing']);
    }

    public function results($jobId)
    {
        $results = VerificationResult::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($results);
    }

    public function export($jobId, Request $request)
    {
        $request->validate(['format' => 'required|in:csv,json']);
        $format = $request->input('format');
        
        $results = VerificationResult::where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->get();

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($results) {
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Email', 'Status', 'Risk Score', 'SMTP', 'Catch All', 'Disposable']);
                foreach ($results as $result) {
                    fputcsv($output, [
                        $result->email,
                        $result->status,
                        $result->risk_score,
                        $result->smtp,
                        $result->catch_all ? 'Yes' : 'No',
                        $result->disposable ? 'Yes' : 'No',
                    ]);
                }
                fclose($output);
            }, 'verification-results.csv');
        }

        return response()->json($results);
    }
}
