<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\IntakeForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntakeFormController extends Controller
{
    public function create()
    {
        $artists = User::where('role', 'artist')->get();
        return view('intake-form.create', compact('artists'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'artist_id' => 'required|exists:users,id',
            'tattoo_style' => 'required|string|max:255',
            'tattoo_description' => 'required|string',
            'placement' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'reference_images.*' => 'nullable|image|max:5120', // 5MB max per image
            'budget_range' => 'required|string|max:255',
            'additional_notes' => 'nullable|string',
            'has_previous_tattoos' => 'required|boolean',
            'medical_conditions' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Create conversation
            $conversation = Conversation::create([
                'artist_id' => $validated['artist_id'],
                'client_id' => auth()->id(),
                'status' => 'pending',
            ]);

            // Handle file uploads
            $referenceImages = [];
            if ($request->hasFile('reference_images')) {
                foreach ($request->file('reference_images') as $image) {
                    $path = $image->store('reference-images', 'public');
                    $referenceImages[] = $path;
                }
            }

            // Create intake form
            $intakeForm = $conversation->intakeForm()->create([
                ...$validated,
                'reference_images' => $referenceImages,
            ]);

            return redirect()->route('conversations.show', $conversation)
                ->with('success', 'Your intake form has been submitted successfully!');
        });
    }
}
