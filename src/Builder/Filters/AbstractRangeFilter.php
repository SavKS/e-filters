<?php

namespace Savks\EFilters\Builder\Filters;

use Savks\EFilters\Builder\Criteria\AbstractRangeCriteria;

abstract class AbstractRangeFilter extends AbstractFilter
{
    protected const MAX_INT = 2147483647;

    /**
     * @param float|int $value
     */
    abstract protected function normalize($value);

    /**
     * @return float|int
     */
    abstract public function minValue();

    /**
     * @return float|int
     */
    abstract public function maxValue();

    /**
     * @param AbstractRangeCriteria $criteria
     * @return $this
     */
    public function setCriteria(AbstractRangeCriteria $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @return array
     */
    public function selected(): array
    {
        if (! isset($this->criteria)) {
            return [];
        }

        return [
            'min' => $this->criteria->minValue,
            'max' => $this->criteria->maxValue,
        ];
    }
}
