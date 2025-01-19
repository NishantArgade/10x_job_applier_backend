<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class PageConfigurationService
{
    const PAGE_DASHBOARD = 1;
    const PAGE_DASHBOARD_MANAGE = 2;
    const PAGE_APPLICATIONS = 3;
    const PAGE_TEMPLATES = 4;
    const PAGE_RESUMES = 5;
    const PAGE_PROFILE = 6;
    const PAGE_SETTINGS = 7;
    const PAGE_LOGOUT = 8;

    public function getFrontendUrlsMapping()
    {
        return [
            self::PAGE_DASHBOARD => '/dashboard',
            self::PAGE_DASHBOARD_MANAGE => '/dashboard/manage',
            self::PAGE_APPLICATIONS => '/applications',
            self::PAGE_TEMPLATES => '/templates',
            self::PAGE_RESUMES => '/resumes',
            self::PAGE_PROFILE => '/profile',
            self::PAGE_SETTINGS => '/settings',
            self::PAGE_LOGOUT => '/logout',
        ];
    }

    public function getRedirectURL(string|int $pathOrPageCode = null): int|string
    {
        $pageUrls = $this->getFrontendUrlsMapping();

        if ($pathOrPageCode) {
            return $pageUrls[$pathOrPageCode] ?? $pathOrPageCode;
        }

        if (Auth::user()->isAdmin()) {
            return $pageUrls[self::PAGE_DASHBOARD_MANAGE];
        }

        return $pageUrls[self::PAGE_DASHBOARD];
    }

    /**
     * Get all page configs.
     *
     * @return array
     */
    public function getAllPageConfigs(): array
    {
        return [
            self::PAGE_DASHBOARD => [
                'actions' => [],
                'settings' => [],
            ],
        ];
    }
}
