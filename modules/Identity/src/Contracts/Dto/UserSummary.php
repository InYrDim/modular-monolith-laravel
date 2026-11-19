<?php

namespace Modules\Identity\Contracts\Dto;

/**
 * Data crossing the Identity module boundary.
 *
 * Expose only the fields consumers need, never an Eloquent model.
 */
final readonly class UserSummary
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
    ) {}
}
