<?php

namespace App\Http\Controllers;

class NaukriBotController extends Controller
{
    public function startProfileUpdate()
    {
        $laravelRoot = base_path();
        $command = "cd /d $laravelRoot && php artisan naukri:profile-update";

        $vbs = <<<VBS
        Set WshShell = CreateObject("WScript.Shell")
        WshShell.Run "cmd.exe /c $command", 0
        VBS;

        $vbsPath = storage_path('app/hide_bot_runner.vbs');

        $storageAppDir = dirname($vbsPath);
        if (! is_dir($storageAppDir)) {
            mkdir($storageAppDir, 0755, true);
        }

        file_put_contents($vbsPath, $vbs);
        pclose(popen("wscript \"$vbsPath\"", "r"));

        return response()->json([
            'status' => 'Profile update started',
        ]);
    }

    public function startApplyJobs()
    {
        $laravelRoot = base_path();
        $command = "cd /d $laravelRoot && php artisan naukri:apply-jobs";

        $vbs = <<<VBS
        Set WshShell = CreateObject("WScript.Shell")
        WshShell.Run "cmd.exe /c $command", 0
        VBS;

        $vbsPath = storage_path('app/hide_bot_runner.vbs');

        $storageAppDir = dirname($vbsPath);
        if (! is_dir($storageAppDir)) {
            mkdir($storageAppDir, 0755, true);
        }

        file_put_contents($vbsPath, $vbs);
        pclose(popen("wscript \"$vbsPath\"", "r"));

        return response()->json([
            'status' => 'Job application process started',
        ]);
    }

    public function stop()
    {
        exec("taskkill /IM chrome.exe /F", $output, $status);

        if ($status === 0) {
            $pidFile = storage_path('app/naukri_bot_pid.txt');
            if (file_exists($pidFile)) {
                unlink($pidFile);
            }

            return response()->json(['status' => 'All Chrome processes stopped forcefully']);
        } else {
            return response()->json(['error' => 'Failed to stop Chrome processes', 'output' => $output]);
        }
    }
}
