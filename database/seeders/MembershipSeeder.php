<?php

namespace Database\Seeders;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MembershipSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::query()->pluck('id');

        DB::transaction(function () use ($userIds): void {
            foreach (Community::query()->get() as $community) {
                $creatorId = $community->created_by;

                $candidates = $userIds
                    ->reject(fn (int $id): bool => $id === $creatorId)
                    ->shuffle()
                    ->take(random_int(5, 25))
                    ->values();

                $admins = $candidates->take(random_int(1, 3));
                $banned = $candidates->slice(-random_int(0, 2));

                foreach ($candidates as $userId) {
                    $isAdmin = $admins->contains($userId);
                    $isBanned = ! $isAdmin && $banned->contains($userId);

                    $community->members()->attach($userId, [
                        'role' => $isAdmin ? CommunityRole::Admin->value : CommunityRole::Member->value,
                        'is_creator' => false,
                        'banned_at' => $isBanned ? now() : null,
                        'banned_by' => $isBanned ? $creatorId : null,
                    ]);
                }

                $community->members_count = $community->members()
                    ->wherePivotNull('banned_at')
                    ->count();
                $community->save();
            }
        });
    }
}
