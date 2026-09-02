<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\ImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            User::factory()->create([
                'name' => 'Dev User',
                'username' => 'dev',
                'email' => 'dev@redesblog.test',
                'avatar_path' => ImageGenerator::store('avatars', 256, 256),
            ]);

            User::factory()
                ->count(40)
                ->create()
                ->each(function (User $user, int $index): void {
                    // Roughly a third of the accounts carry a real avatar blob.
                    if ($index % 3 === 0) {
                        $user->avatar_path = ImageGenerator::store('avatars', 256, 256);
                        $user->save();
                    }
                });
        });
    }
}
