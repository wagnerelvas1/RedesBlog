<?php

namespace App\Services;

/**
 * The counters a vote mutation produced, returned so the client can update
 * without refetching the whole page.
 */
final readonly class VoteResult
{
    public function __construct(
        public int $score,
        public int $upvotesCount,
        public int $downvotesCount,
        public int $viewerVote,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'upvotes_count' => $this->upvotesCount,
            'downvotes_count' => $this->downvotesCount,
            'viewer_vote' => $this->viewerVote,
        ];
    }
}
