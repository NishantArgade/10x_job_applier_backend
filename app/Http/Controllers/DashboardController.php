<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = $this->getStatsData();

        return [
            'stats' => $stats,
        ];
    }

    private function getStatsData(): array
    {
        $application = Application::query()
            ->selectRaw("
                status, 
                COUNT(*) as total,
                SUM(recruitor_reply) AS recruiter_replies
            ")
            ->groupBy('status')
            ->get();

        return [
            [
                'key' => 'pending',
                'title' => 'Pending',
                'iconType' => 'clock',
                'value' => $application->firstWhere('status', 'pending')?->total ?? 0,
                'tooltip' => 'Applications that are yet to be processed.'
            ],
            [
                'key' => 'sent',
                'title' => 'Sent',
                'iconType' => 'paper-plane',
                'value' => $application->firstWhere('status', 'sent')?->total ?? 0,
                'tooltip' => 'Applications successfully submitted.'
            ],
            [
                'key' => 'failed',
                'title' => 'Failed',
                'iconType' => 'x-circle',
                'value' => $application->firstWhere('status', 'failed')?->total ?? 0,
                'tooltip' => 'Applications that failed to submit.'
            ],
            [
                'key' => 'recruiter_replies',
                'title' => 'Recruiter Replies',
                'iconType' => 'chat-bubble',
                'value' => $application->sum('recruiter_replies'),
                'tooltip' => 'Total replies from recruiters.'
            ]
        ];
    }
}
