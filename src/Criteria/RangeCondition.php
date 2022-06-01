<?php

namespace Savks\EFilters\Criteria;

use Closure;
use Savks\ESearch\Builder\DSL\Query;

class RangeCondition implements Condition
{
    /**
     * @param string $blockId
     * @param int|float|null $minValue
     * @param int|float|null $maxValue
     * @param Closure(int|float|null, int|float|null): Query $queryResolver
     */
    public function __construct(
        public readonly string $blockId,
        public readonly int|float|null $minValue,
        public readonly int|float|null $maxValue,
        protected readonly Closure $queryResolver
    ) {
        //
    }

    /**
     * @return Query
     */
    public function toQuery(): Query
    {
        return \call_user_func($this->queryResolver, $this->minValue, $this->maxValue);
    }
}
