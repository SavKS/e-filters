<?php

namespace Savks\EFilters\Builder\Filters;

use Savks\EFilters\Builder\Criteria\AbstractChooseCriteria;

abstract class AbstractChooseFilter extends AbstractFilter
{
    /**
     * @param AbstractChooseCriteria $criteria
     * @return $this
     */
    public function setCriteria(AbstractChooseCriteria $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @return array
     */
    public function selected(): array
    {
        return $this->criteria->values ?? [];
    }
}
