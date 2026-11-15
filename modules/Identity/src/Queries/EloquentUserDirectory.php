<?php

namespace Modules\Identity\Queries;

use Modules\Identity\Contracts\Dto\UserSummary;
use Modules\Identity\Contracts\UserDirectory;
use Modules\Identity\Models\User;

class EloquentUserDirectory implements UserDirectory
{
    /**
     * @param  list<int>  $ids
     * @return array<int, UserSummary>
     */
    public function summaries(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => new UserSummary($user->id, $user->name, $user->email),
            ])
            ->all();
    }
}
