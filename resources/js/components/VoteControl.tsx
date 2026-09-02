import { useAuthUser } from '@/hooks/usePage';
import { cn } from '@/lib/utils';
import { compactNumber } from '@/lib/format';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { vote as postVote, unvote as postUnvote } from '@/routes/posts';
import {
    vote as commentVote,
    unvote as commentUnvote,
} from '@/routes/comments';
import { login } from '@/routes';

export type VoteControlProps = {
    votableType: 'post' | 'comment';
    id: number;
    score: number;
    viewerVote: number;
    orientation?: 'vertical' | 'horizontal';
    className?: string;
};

/**
 * Up/down control with an optimistic score update that rolls back if the
 * request fails. Guests see the arrows but are sent to the login screen.
 */
export function VoteControl({
    votableType,
    id,
    score,
    viewerVote,
    orientation = 'vertical',
    className,
}: VoteControlProps) {
    const user = useAuthUser();
    const [state, setState] = useState({ score, vote: viewerVote });

    function submit(next: number) {
        if (!user) {
            router.visit(login().url);

            return;
        }

        const previous = state;
        const clearing = next === state.vote;
        const value = clearing ? 0 : next;

        // Optimistic: apply the delta immediately, revert on failure.
        setState({
            score: state.score - state.vote + value,
            vote: value,
        });

        const endpoint =
            votableType === 'post'
                ? clearing
                    ? postUnvote(id)
                    : postVote(id)
                : clearing
                  ? commentUnvote(id)
                  : commentVote(id);

        router.visit(endpoint.url, {
            method: endpoint.method,
            data: clearing ? {} : { value: next },
            preserveScroll: true,
            preserveState: true,
            onError: () => setState(previous),
        });
    }

    const vertical = orientation === 'vertical';

    return (
        <div
            className={cn(
                'flex items-center gap-1',
                vertical ? 'flex-col' : 'flex-row',
                className,
            )}
        >
            <Arrow
                direction="up"
                active={state.vote === 1}
                onClick={() => submit(1)}
            />
            <span
                className={cn(
                    'text-xs font-bold tabular-nums',
                    state.vote === 1 && 'text-upvote',
                    state.vote === -1 && 'text-downvote',
                    state.vote === 0 && 'text-text',
                )}
            >
                {compactNumber(state.score)}
            </span>
            <Arrow
                direction="down"
                active={state.vote === -1}
                onClick={() => submit(-1)}
            />
        </div>
    );
}

function Arrow({
    direction,
    active,
    onClick,
}: {
    direction: 'up' | 'down';
    active: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            aria-label={direction === 'up' ? 'Upvote' : 'Downvote'}
            className={cn(
                'hover:bg-surface-2 cursor-pointer rounded p-0.5 text-sm leading-none transition',
                active
                    ? direction === 'up'
                        ? 'text-upvote'
                        : 'text-downvote'
                    : 'text-muted',
            )}
        >
            <span aria-hidden="true">{direction === 'up' ? '▲' : '▼'}</span>
        </button>
    );
}
