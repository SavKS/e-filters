<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\RangeCondition;
use Savks\EFilters\Support\Blocks\RangeBlockDeclaration;

abstract class RangeCriteria
{
    public readonly RangeCondition $condition;

    public function __construct(
        public readonly float|int|null $minValue,
        public readonly float|int|null $maxValue,
        public readonly ?RangeBlockDeclaration $blockDeclaration = null
    ) {
        $this->condition = $this->defineCondition();
    }

    abstract protected function defineCondition(): RangeCondition;

    public function exists(): bool
    {
        return $this->minValue !== null || $this->maxValue !== null;
    }
}
