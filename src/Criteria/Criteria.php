<?php

namespace Savks\EFilters\Criteria;

abstract class Criteria
{
    /**
     * @return bool
     */
    abstract public function exists(): bool;

    /**
     * @param Conditions $conditions
     * @return void
     */
    abstract public function defineConditions(Conditions $conditions): void;
}
