<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ApplicationsImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportJobApplications
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'applications_csv' => 'required|mimes:csv,txt',
            'template_id' => 'exists:templates,id',
            'resume_id' => 'exists:resumes,id',
        ]);

        Excel::import(
            new ApplicationsImport($validated['template_id'], $validated['resume_id']),
            $validated['applications_csv']
        );

        return 'Application data imported successfully';
    }
}
