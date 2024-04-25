<?php

namespace App\Http\Controllers;

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

        if ($latestVersion && version_compare($latestVersion, $currentVersion, '>')) {
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

    private function getLatestVersion()
    {
        // You can fetch the latest version from a remote API or database
        return '1.2.0'; // Example latest version
    }
}
