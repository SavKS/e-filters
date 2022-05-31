<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\Conditions;
use Savks\EFilters\Criteria\Criteria;
use Savks\EFilters\Support\Blocks\RangeBlockDeclaration;

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
    protected ?RangeBlockDeclaration $blockDeclaration;

    /**
     * @var Conditions
     */
    protected Conditions $conditions;

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

        $this->defineConditions(
            $this->conditions = new Conditions()
        );
    }

    /**
     * @return Conditions
     */
    public function conditions(): Conditions
    {
        return $this->conditions;
    }

    /**
     * @return RangeBlockDeclaration|null
     */
    public function blockDeclaration(): ?RangeBlockDeclaration
    {
        return $this->blockDeclaration;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->minValue !== null || $this->maxValue !== null;
    }
}
