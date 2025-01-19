<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $resumes = Resume::all();

        return $resumes;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'resume' => 'required|file|mimes:pdf|max:10240',
            'is_active' => 'nullable|boolean',
        ]);

        $file = $validated['resume'];
        $uuid = Str::uuid();
        $fileName = $uuid.'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('resumes', $fileName, 'upload');

        $resume = Resume::create([
            'uuid' => $uuid,
            'file_name' => $fileName,
            'mime_type' => $file->getMimeType(),
            'path' => $filePath,
            'is_active' => $validated['is_active'] ?? false,
            'size' => $file->getSize(),
            'user_id' => auth()->id() 
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully!',
            'resume' => $resume,
        ], 201);
    }
}
