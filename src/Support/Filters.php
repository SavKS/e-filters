<?php

namespace Savks\EFilters\Support;

use Savks\ESearch\Builder\Builder;

use Savks\EFilters\Support\Criteria\{
    ChooseCriteria,
    RangeCriteria
};

class Filters
{
    /**
     * @var Builder
     */
    protected Builder $query;

    /**
     * @var ChooseCriteria[]|RangeCriteria[]
     */
    protected array $criteria = [];

    /**
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param ChooseCriteria $criteria
     * @return $this
     */
    public function choose(ChooseCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    /**
     * @param RangeCriteria $criteria
     * @return $this
     */
    public function range(RangeCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }
}
