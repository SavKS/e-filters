<?php

namespace Savks\EFilters\Criteria;

use Savks\ESearch\Builder\DSL\Query;

class Condition
{
    /**
     * @param string $id
     * @param Query $query
     */
    public function __construct(
        public readonly string $id,
        public readonly Query $query
    ) {
        //
    }
}
