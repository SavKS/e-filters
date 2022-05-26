<?php

namespace Savks\EFilters\Builder\Criteria;

class Condition
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var array
     */
    public array $query;

    /**
     * Condition constructor.
     * @param string $id
     * @param array $query
     */
    public function __construct(string $id, array $query)
    {
        $this->id = $id;
        $this->query = $query;
    }
}
