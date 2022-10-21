<?php

namespace Savks\EFilters\Criteria;

use Savks\ESearch\Builder\DSL\Query;

interface Condition
{
    public function toQuery(): Query;
}
