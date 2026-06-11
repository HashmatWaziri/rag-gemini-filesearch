# App/Actions guidelines

- The legacy (pre-GLC) parts of this application use the Action pattern and prefer for much logic to live in reusable and composable Action classes. New GLC Phase 1 domain logic lives in service classes under `App\Services\Glc\<Domain>` instead (see `.ai/guidelines/glc-platform.blade.php` and `docs/glc-implementation-contract.md`); the conventions below still apply when touching existing actions.
- Actions live in `app/Actions`, they are named based on what they do, with no suffix.
- Actions will be called from many different places: jobs, commands, HTTP requests, API requests, MCP requests, and more.
- Create dedicated Action classes for business logic with a single `handle()` method.
- Inject dependencies via constructor using private properties.
- Create new actions with `php artisan make:action "{name}" --no-interaction`
- Wrap complex operations in `DB::transaction()` within actions when multiple models are involved.
- Some actions won't require dependencies via `__construct` and they can use just the `handle()` method.

@boostsnippet('Example action class', 'php')
<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CreateFavorite
{
    public function __construct(private FavoriteService $favorites)
    {
        //
    }

    public function handle(User $user, string $favorite): bool
    {
        return $this->favorites->add($user, $favorite);
    }
}
@endboostsnippet
