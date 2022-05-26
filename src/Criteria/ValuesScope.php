<?php

namespace Savks\EFilters\Criteria;

class ValuesScope
{
    /**
     * @param array $values
     */
    public function __construct(
        public readonly array $values
    ) {
        //
    }
}
