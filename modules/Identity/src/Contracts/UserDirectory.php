<?php

namespace Modules\Identity\Contracts;

use Modules\Identity\Contracts\Dto\UserSummary;

/**
 * Public API of the Identity module.
 *
 * Declare only the methods the consuming module needs, and return Contracts\Dto
 * objects instead of Identity models.
 */
interface UserDirectory
{
    /**
     * @param  list<int>  $ids
     * @return array<int, UserSummary> Keyed by user id; unknown ids are omitted.
     */
    public function summaries(array $ids): array;
}
