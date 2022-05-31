<?php

namespace Savks\EFilters\Criteria;

use Savks\EFilters\Blocks\BlockDeclaration;

abstract class Criteria
{
    /**
     * @return bool
     */
    abstract public function exists(): bool;

    /**
     * @return BlockDeclaration|null
     */
    abstract public function blockDeclaration(): ?BlockDeclaration;

    /**
     * @return Conditions
     */
    abstract public function conditions(): Conditions;

    /**
     * @param Conditions $conditions
     * @return void
     */
    abstract protected function defineConditions(Conditions $conditions): void;
}
