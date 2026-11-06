<?php

namespace App\Http\Controllers;

use App\Models\StoreInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreInquiryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            abort(422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'inquiry_type' => ['required', Rule::in(['general', 'complaint', 'suggestion', 'product_request'])],
            'product_name' => ['nullable', 'string', 'max:160', Rule::requiredIf($request->input('inquiry_type') === 'product_request')],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        if (empty($validated['email']) && empty($validated['phone'])) {
            throw ValidationException::withMessages([
                'email' => 'Add an email address or phone number so the owner can reply.',
            ]);
        }

        StoreInquiry::query()->create($validated);

        return back()->with('inquiry_submitted', 'Thanks for getting in touch. The store owner has received your message.');
    }
}
