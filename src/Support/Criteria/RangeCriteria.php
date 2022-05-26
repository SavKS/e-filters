<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\Criteria;
use Savks\EFilters\Support\Blocks\RangeBlockDeclaration;
use Savks\ESearch\Builder\DSL\Query;

abstract class RangeCriteria extends Criteria
{
    /**
     * @var int|float|null
     */
    protected int|float|null $minValue;

    /**
     * @var int|float|null
     */
    protected int|float|null $maxValue;

    /**
     * @var RangeBlockDeclaration|null
     */
    protected ?RangeBlockDeclaration $filterDeclaration;

    /**
     * @param float|int|null $minValue
     * @param float|int|null $maxValue
     * @param RangeBlockDeclaration|null $filterDeclaration
     */
    public function __construct(
        float|int|null $minValue,
        float|int|null $maxValue,
        RangeBlockDeclaration $filterDeclaration = null
    ) {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;

        $this->filterDeclaration = $filterDeclaration;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->minValue !== null || $this->maxValue !== null;
    }

    /**
     * @return Query
     */
    abstract public function toQuery(): Query;
}
