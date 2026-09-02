<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Domain rule violation in the community/membership flows. Controllers turn
 * these into flash errors.
 */
class CommunityException extends RuntimeException
{
    public static function creatorCannotLeave(): self
    {
        return new self('The creator cannot leave their own community.');
    }

    public static function creatorIsProtected(): self
    {
        return new self('The community creator cannot be demoted, banned or removed.');
    }

    public static function alreadyMember(): self
    {
        return new self('You already belong to this community.');
    }

    public static function banned(): self
    {
        return new self('You have been banned from this community.');
    }

    public static function notMember(): self
    {
        return new self('That user is not a member of this community.');
    }

    public static function cannotActOnSelf(): self
    {
        return new self('You cannot perform this action on yourself.');
    }
}
