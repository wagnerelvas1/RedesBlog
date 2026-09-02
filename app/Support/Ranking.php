<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Score-derived ranking formulas used by the feed and comment sorts.
 */
final class Ranking
{
    /** Epoch baseline for the hotness decay: 2025-01-01T00:00:00Z. */
    private const EPOCH = 1735689600;

    private const WILSON_Z = 1.281551565545;

    /**
     * Reddit-style hotness: sign-weighted log of the score plus an age term, so
     * newer posts outrank equally-scored older ones.
     */
    public static function hot(int $score, ?CarbonInterface $createdAt): float
    {
        $sign = $score <=> 0;
        $order = log10(max(abs($score), 1));
        $seconds = ($createdAt?->getTimestamp() ?? time()) - self::EPOCH;

        return round($sign * $order + $seconds / 45000, 7);
    }

    /**
     * Wilson lower bound: the "Best" comment sort. Rewards a high positive
     * ratio backed by enough votes to be trustworthy.
     */
    public static function best(int $upvotes, int $downvotes): float
    {
        $total = $upvotes + $downvotes;

        if ($total === 0) {
            return 0.0;
        }

        $phat = $upvotes / $total;
        $z = self::WILSON_Z;

        return round(
            ($phat + $z * $z / (2 * $total)
                - $z * sqrt(($phat * (1 - $phat) + $z * $z / (4 * $total)) / $total))
            / (1 + $z * $z / $total),
            7,
        );
    }
}
