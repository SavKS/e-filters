<?php

namespace Savks\EFilters\Criteria;

use Closure;

class ChooseConditions
{
    /**
     * @var ChooseCondition[]
     */
    protected array $conditions = [];

    /**
     * @return ChooseCondition[]
     */
    public function all(): array
    {
        return \array_values($this->conditions);
    }

    public function add(string $blockId, array $values, Closure $queryResolver): static
    {
        $this->conditions[$blockId] = new ChooseCondition($blockId, $values, $queryResolver);

        return $this;
    }

    public function tryFindByBlockId(string $blockId): ?ChooseCondition
    {
        return $this->conditions[$blockId] ?? null;
    }
}
