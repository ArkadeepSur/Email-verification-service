<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetsService;
use App\Services\HubSpotService;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function syncGoogleSheets(Request $request, GoogleSheetsService $sheets)
    {
        $request->validate(['spreadsheet_id' => 'required', 'range' => 'required']);
        $jobId = $sheets->importEmails($request->input('spreadsheet_id'), $request->input('range'));

        return response()->json(['job_id' => $jobId]);
    }

    public function syncHubspot(Request $request, HubSpotService $hubspot)
    {
        $jobId = $hubspot->syncContacts();

        return response()->json(['job_id' => $jobId]);
    }
}

