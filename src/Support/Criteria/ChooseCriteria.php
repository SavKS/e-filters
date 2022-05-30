<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\Criteria;
use Savks\EFilters\Support\Blocks\ChooseBlockDeclaration;

abstract class ChooseCriteria extends Criteria
{
    /**
     * @var array
     */
    protected array $values;

    /**
     * @var ChooseBlockDeclaration|null
     */
    protected ?ChooseBlockDeclaration $blockDeclaration;

    /**
     * @param array $values
     * @param ChooseBlockDeclaration|null $blockDeclaration
     */
    public function __construct(array $values, ChooseBlockDeclaration $blockDeclaration = null)
    {
        $this->values = $values;
        $this->blockDeclaration = $blockDeclaration;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->values);
    }
}
