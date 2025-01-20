<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $profile = Profile::with(['templates', 'resumes', 'activeTemplate', 'activeResume'])
            ->firstWhere('user_id', $request->user()->id);

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'profile_id' => 'required|integer',
            'name' => 'nullable|string',
            'role' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'skills' => 'nullable|array',
            'linkedin_url' => 'nullable|string',
            'naukri_url' => 'nullable|string',
            'github_url' => 'nullable|string',
            'website_url' => 'nullable|string',
            'current_company' => 'nullable|string',
            'current_designation' => 'nullable|string',
            'current_location' => 'nullable|string',
            'experience' => 'nullable|float',
            'notice_period' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'resume_id' => 'nullable|integer'
        ]);

        $profile = Profile::findOrFail($validated['profile_id']);

        $profile->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'skills' => $validated['skills'],
            'linkedin_url' => $validated['linkedin_url'],
            'naukri_url' => $validated['naukri_url'],
            'github_url' => $validated['github_url'],
            'website_url' => $validated['website_url'],
            'current_company' => $validated['current_company'],
            'current_designation' => $validated['current_designation'],
            'current_location' => $validated['current_location'],
            'experience' => $validated['experience'],
            'notice_period' => $validated['notice_period'],
        ]);

        if (isset($validated['template_id'])) {
            $profile->update(['template_id' => $validated['template_id']]);
        }

        if (isset($validated['resume_id'])) {
            $profile->update(['resume_id' => $validated['resume_id']]);
        }

        return response()->json($profile);
    }

    public function destroy(Request $request)
    {
        $profile = Profile::firstWhere('user_id', $request->user()->id);

        $profile->delete();

        return response()->json(['message' => 'Profile deleted successfully']);
    }
}
