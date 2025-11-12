<?php

namespace App\Nova\Tools;

use App\Models\Feedback as FeedbackModel;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

class FeedbackTool extends Tool
{
    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('Feedback')
            ->path('/tools/feedback')
            ->icon('chat');
    }

    /**
     * Perform any final formatting of the given validation rules.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function rules(Request $request, array $rules): array
    {
        return $rules;
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function renderNavigation()
    {
        return view('nova.feedback-navigation');
    }

    /**
     * Build the view that renders the tool.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render(Request $request)
    {
        return view('nova.feedback', [
            'feedback' => FeedbackModel::where('user_id', $request->user()->id)
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Handle the form submission.
     */
    public function handle(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:bug,feature,ux,general',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        FeedbackModel::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'new',
        ]);

        return response()->json(['message' => 'Feedback submitted successfully. Thank you!']);
    }
}
