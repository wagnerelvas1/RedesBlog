<?php

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

it('runs on postgres against the dedicated test database', function (): void {
    expect(DB::connection()->getDriverName())->toBe('pgsql')
        ->and(DB::selectOne('select current_database() as name')->name)
        ->toBe('redesblog_testing');
});

it('stores the identity columns as citext', function (): void {
    $columns = collect(DB::select(<<<'SQL'
        SELECT c.relname AS table_name, a.attname AS column_name, t.typname AS type
        FROM pg_attribute a
        JOIN pg_class c ON c.oid = a.attrelid
        JOIN pg_type t ON t.oid = a.atttypid
        WHERE t.typname = 'citext'
    SQL))->map(fn ($row): string => $row->table_name.'.'.$row->column_name);

    expect($columns)->toContain('users.username', 'users.email', 'communities.name');
});

it('creates the postgres-specific indexes', function (): void {
    $indexes = collect(DB::select("SELECT indexname FROM pg_indexes WHERE schemaname = 'public'"))
        ->pluck('indexname');

    expect($indexes)->toContain('community_user_one_creator', 'comments_path_prefix');
});

it('enforces one creator per community at the database level', function (): void {
    $community = communityOwnedBy();
    $other = User::factory()->create();

    expect(fn () => $community->members()->attach($other->id, [
        'role' => 'admin',
        'is_creator' => true,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('treats community names case-insensitively', function (): void {
    communityOwnedBy(null, ['name' => 'webdev']);

    expect(Community::query()->where('name', 'WebDev')->exists())->toBeTrue();

    expect(fn () => Community::factory()->create(['name' => 'WEBDEV']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('resolves a community route binding case-insensitively', function (): void {
    communityOwnedBy(null, ['name' => 'webdev']);

    $this->get(route('communities.show', 'WEBDEV'))->assertOk();
});
