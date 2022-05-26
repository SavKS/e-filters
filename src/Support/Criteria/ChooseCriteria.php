<?php

namespace Savks\EFilters\Support\Criteria;

use Savks\EFilters\Criteria\Criteria;
use Savks\EFilters\Support\Blocks\ChooseBlockDeclaration;
use Savks\ESearch\Builder\DSL\Query;

abstract class ChooseCriteria extends Criteria
{
    /**
     * @var array
     */
    protected array $values;

    /**
     * @var ChooseBlockDeclaration|null
     */
    protected ?ChooseBlockDeclaration $filterDeclaration;

    /**
     * @param array $values
     * @param ChooseBlockDeclaration|null $filterDeclaration
     */
    public function __construct(array $values, ChooseBlockDeclaration $filterDeclaration = null)
    {
        $this->values = $values;
        $this->filterDeclaration = $filterDeclaration;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return !empty($this->values);
    }

    /**
     * @return Query[]
     */
    abstract public function toQueries(): array;
}
