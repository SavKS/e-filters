<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\RangeCondition;
use Savks\EFilters\Support\Blocks\RangeBlockDeclaration;

abstract class RangeCriteria
{
    /**
     * @var int|float|null
     */
    public readonly int|float|null $minValue;

    /**
     * @var int|float|null
     */
    public readonly int|float|null $maxValue;

    /**
     * @var RangeBlockDeclaration|null
     */
    public readonly ?RangeBlockDeclaration $blockDeclaration;

    /**
     * @var RangeCondition
     */
    public readonly RangeCondition $condition;

    /**
     * @param float|int|null $minValue
     * @param float|int|null $maxValue
     * @param RangeBlockDeclaration|null $blockDeclaration
     */
    public function __construct(
        float|int|null $minValue,
        float|int|null $maxValue,
        RangeBlockDeclaration $blockDeclaration = null
    ) {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;

        $this->blockDeclaration = $blockDeclaration;

        $this->condition = $this->defineCondition();
    }

    /**
     * @return RangeCondition
     */
    abstract protected function defineCondition(): RangeCondition;

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->minValue !== null || $this->maxValue !== null;
    }
}
