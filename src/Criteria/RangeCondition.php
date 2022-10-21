<?php

namespace Savks\EFilters\Criteria;

use Closure;
use Savks\ESearch\Builder\DSL\Query;

class RangeCondition implements Condition
{
    public function __construct(
        public readonly string $blockId,
        public readonly int|float|null $minValue,
        public readonly int|float|null $maxValue,
        protected readonly Closure $queryResolver
    ) {
        //
    }

    public function toQuery(): Query
    {
        return \call_user_func($this->queryResolver, $this->minValue, $this->maxValue);
    }
}
