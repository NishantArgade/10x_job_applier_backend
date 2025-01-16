<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'nullable|string',
            'body' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $payload = [
            'name' => $validated['name'],
            'body' => $validated['body'],
            'user_id' => 1, //auth()->id(),
            ...(isset($validated['subject'])) ? ['subject' => $validated['subject']] : [],
            ...(isset($validated['is_active'])) ? ['is_active' => $validated['is_active']] : []
        ];

        $template = Template::create($payload);

        return response()->json([
            'message' => 'Template created successfully',
            'template' => $template
        ], 201);
    }
}
