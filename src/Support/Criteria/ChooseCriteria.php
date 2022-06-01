<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\ChooseConditions;
use Savks\EFilters\Support\Blocks\ChooseBlockDeclaration;

abstract class ChooseCriteria
{
    /**
     * @var array
     */
    public readonly array $values;

    /**
     * @var ChooseBlockDeclaration|null
     */
    public readonly ?ChooseBlockDeclaration $blockDeclaration;

    /**
     * @var ChooseConditions
     */
    public readonly ChooseConditions $conditions;

    /**
     * @param array $values
     * @param ChooseBlockDeclaration|null $blockDeclaration
     */
    public function __construct(array $values, ChooseBlockDeclaration $blockDeclaration = null)
    {
        $this->values = $values;
        $this->blockDeclaration = $blockDeclaration;

        $this->defineConditions(
            $this->conditions = new ChooseConditions()
        );
    }

    /**
     * @param ChooseConditions $conditions
     * @return void
     */
    abstract protected function defineConditions(ChooseConditions $conditions): void;

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return ! empty($this->values);
    }
}
