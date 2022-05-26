<?php

namespace Savks\EFilters\Criteria;

abstract class Criteria
{
    /**
     * @return bool
     */
    abstract public function exists(): bool;
}
