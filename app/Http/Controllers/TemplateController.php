<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = Template::all();

        return response()->json([
            'templates' => $templates
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'nullable|string',
            'body' => 'required|string'
        ]);

        $payload = [
            'name' => $validated['name'],
            'body' => $this->cleanTailwindStyles($validated['body']),
            'user_id' => auth()->id(),
            ...(isset($validated['subject'])) ? ['subject' => $validated['subject']] : []
        ];

        $template = Template::create($payload);

        return response()->json([
            'message' => 'Template created successfully',
            'template' => $template
        ], 201);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'subject' => 'nullable|string',
            'body' => 'required|string'
        ]);

        $payload = [
            'name' => $validated['name'],
            'body' => $this->cleanTailwindStyles($validated['body']),
            ...(isset($validated['subject'])) ? ['subject' => $validated['subject']] : []
        ];

        $template->update($payload);

        return response()->json([
            'message' => 'Template updated successfully',
            'template' => $template
        ]);
    }

    public function destroy(Request $request, Template $template)
    {
        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully'
        ]);
    }

    private function cleanTailwindStyles($html)
    {
        return preg_replace('/style="[^"]*--tw-[^"]*"/i', '', $html);
    }

}
