<?php

namespace Savks\EFilters\Criteria;

use Closure;
use Savks\ESearch\Builder\DSL\Query;

class ChooseCondition implements Condition
{
    public function __construct(
        public readonly string $blockId,
        public readonly array $values,
        protected readonly Closure $queryResolver
    ) {
    }

    public function toQuery(): Query
    {
        return \call_user_func($this->queryResolver, $this->values);
    }
}
