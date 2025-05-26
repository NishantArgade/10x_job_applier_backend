<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        $resumes = Resume::all()->map(function ($resume) {
            $resume->download_url = $this->getPublicUrl($resume);
            return $resume;
        });

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
        $originalFilename = $file->getClientOriginalName();
        $fileName = $uuid.'.'.$file->getClientOriginalExtension();

        // Store in public disk instead of upload disk for public access
        $filePath = $file->storeAs('resumes', $fileName, 'public');

        $resume = Resume::create([
            'uuid' => $uuid,
            'file_name' => $fileName,
            'original_filename' => $originalFilename,
            'mime_type' => $file->getMimeType(),
            'path' => $filePath,
            'is_active' => $validated['is_active'] ?? false,
            'size' => $file->getSize(),
            'user_id' => auth()->id()
        ]);

        // Add download URL to response
        $resume->download_url = $this->getPublicUrl($resume);

        return response()->json([
            'message' => 'Resume uploaded successfully!',
            'resume' => $resume,
        ], 201);
    }

    public function update(Request $request, Resume $resume)
    {
        $validated = $request->validate([
            'is_active' => 'nullable|boolean',
        ]);

        $resume->update([
            'is_active' => $validated['is_active'] ?? false,
        ]);

        // Add download URL to response
        $resume->download_url = $this->getPublicUrl($resume);

        return response()->json([
            'message' => 'Resume updated successfully!',
            'resume' => $resume,
        ]);
    }

    public function destroy(Resume $resume)
    {
        // Delete the actual file from storage
        if (Storage::disk('public')->exists($resume->path)) {
            Storage::disk('public')->delete($resume->path);
        }

        $resume->delete();

        return response()->json([
            'message' => 'Resume deleted successfully!',
        ]);
    }

    /**
     * Get the public accessible URL for a resume
     *
     * @param Resume $resume
     * @return string
     */
    private function getPublicUrl(Resume $resume)
    {
        return url('/api/v1/public-resume/'.$resume->uuid);
    }
}
