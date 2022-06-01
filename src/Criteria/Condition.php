<?php

namespace Savks\EFilters\Criteria;

use Savks\ESearch\Builder\DSL\Query;

interface Condition
{
    /**
     * @return Query
     */
    public function toQuery(): Query;
}
