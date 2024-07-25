<?php

declare(strict_types=1);

namespace Tempest\Database\Exceptions;

use Exception;

final class InvalidRelation extends Exception
{
    public function __construct(string $modelClass, string $relationName)
    {
        parent::__construct("Could not determine the relation '{$relationName}' of {$modelClass}.");
    }
}
