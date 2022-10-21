<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\ChooseConditions;
use Savks\EFilters\Support\Blocks\ChooseBlockDeclaration;

abstract class ChooseCriteria
{
    public readonly ChooseConditions $conditions;

    public function __construct(
        public readonly array $values,
        public readonly ?ChooseBlockDeclaration $blockDeclaration = null
    ) {

        $this->conditions = new ChooseConditions();

        if ($this->values) {
            $this->defineConditions($this->conditions);
        }
    }

    abstract protected function defineConditions(ChooseConditions $conditions): void;

    public function exists(): bool
    {
        return ! empty($this->values);
    }
}
