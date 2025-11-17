<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateUserRecommendationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecommendationService $recommendationService): void
    {
        if ($this->userId) {
            // Generate recommendations for a specific user
            $user = User::find($this->userId);

            if (! $user) {
                Log::warning("User {$this->userId} not found for recommendation generation");

                return;
            }

            $this->generateForUser($user, $recommendationService);
        } else {
            // Generate recommendations for all active users
            $users = User::where('is_active', true)
                ->whereHas('postViews')
                ->get();

            foreach ($users as $user) {
                $this->generateForUser($user, $recommendationService);
            }
        }
    }

    /**
     * Generate recommendations for a single user.
     */
    protected function generateForUser(User $user, RecommendationService $recommendationService): void
    {
        $recommendations = $recommendationService->generateRecommendations($user, 20);

        if ($recommendations->isNotEmpty()) {
            $recommendationService->storeRecommendations($user, $recommendations);
            Log::info("Generated {$recommendations->count()} recommendations for user {$user->id}");
        }
    }
}
