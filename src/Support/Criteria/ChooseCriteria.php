<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Support\Blocks\ChooseBlockDeclaration;

use Savks\EFilters\Criteria\{
    Conditions,
    Criteria
};

abstract class ChooseCriteria extends Criteria
{
    /**
     * @var array
     */
    public readonly array $values;

    /**
     * @var ChooseBlockDeclaration|null
     */
    protected ?ChooseBlockDeclaration $blockDeclaration;

    /**
     * @var Conditions
     */
    protected Conditions $conditions;

    /**
     * @param array $values
     * @param ChooseBlockDeclaration|null $blockDeclaration
     */
    public function __construct(array $values, ChooseBlockDeclaration $blockDeclaration = null)
    {
        $this->values = $values;
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
     * @return ChooseBlockDeclaration|null
     */
    public function blockDeclaration(): ?ChooseBlockDeclaration
    {
        return $this->blockDeclaration;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return ! empty($this->values);
    }
}
