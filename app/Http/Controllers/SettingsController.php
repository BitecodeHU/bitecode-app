<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Salahhusa9\Updater\Facades\Updater;

class SettingsController extends Controller
{
    /*
     * Display the user creation form
     */
    public function index(Request $request): View
    {
        return view('settings.index', [
            'user' => $request->user(),
        ]);
    }

    public function checkUpdate()
    {
        $currentVersion = Config::get('app_version.version');
        // Assuming you have a function to get the latest version from an external source
        $latestVersion = $this->getLatestVersion();

        if ($latestVersion && version_compare($latestVersion, $currentVersion, '>=')) {
            Session::flash('update_status', 'new-version'); // Set session variable for new version
        } else {
            Session::flash('update_status', 'no-new-version'); // Set session variable for no new version
        }

        return response()->json(['message' => 'Update check completed.'], 200);
    }

    public function update()
    {
        // Run the updater logic
        Updater::update();

        // Optionally, you can set a success message in the session
        Session::flash('update_status', 'updated');

        return redirect()->route('settings.index');
    }

    public function getLatestVersion()
    {
        // GitHub repository details
        $owner = env('GITHUB_USERNAME'); // Replace with your GitHub username or organization name
        $repo = env('GITHUB_REPOSITORY'); // Replace with your repository name

        // GitHub API endpoint for latest release
        $url = "https://api.github.com/repos/BitecodeHU/bitecode-app/releases/latest";

        // Make HTTP GET request to GitHub API
        $response = Http::get($url);
        Log::info('GitHub API URL: ' . $url);
        Log::info('GitHub API Response: ' . $response->body());

        $responseData = $response->json();
        Log::info('GitHub API Response Data: ' . print_r($responseData, true));

        // Check if request was successful and response contains version info
        if ($response->successful() && $response->json()) {
            // Extract latest version from response
            $latestVersion = $response->json()['tag_name'];
            return $latestVersion;
        } else {
            // Handle error or fallback to default version
            return '1.0.0'; // Fallback version
        }
    }
}
