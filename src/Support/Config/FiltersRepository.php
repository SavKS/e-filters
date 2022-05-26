<?php

namespace Savks\EFilters\Support\Config;

use Savks\EFilters\Builder\Criteria\CriteriaInterface;
use Savks\EFilters\Builder\Filters\AbstractFilter;
use RuntimeException;

class FiltersRepository
{
    /**
     * @var array
     */
    protected array $items = [];

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param class-string<AbstractFilter> $filterFQN
     * @param class-string<CriteriaInterface> $criterionFQN
     * @return $this
     */
    public function add(string $filterFQN, string $criterionFQN): self
    {
        if (! is_subclass_of($filterFQN, AbstractFilter::class)) {
            throw new RuntimeException("Filter [$filterFQN] must extend " . AbstractFilter::class);
        }

        if (! is_subclass_of($criterionFQN, CriteriaInterface::class)) {
            throw new RuntimeException("Criterion [$criterionFQN] must implement " . CriteriaInterface::class);
        }

        $this->items[] = [$filterFQN, $criterionFQN];

        return $this;
    }

    /**
     * @param string $id
     * @return class-string<AbstractFilter>|null
     */
    public function findById(string $id): ?string
    {
        foreach ($this->all() as [$filterFQN]) {
            if ($filterFQN::id() === $id) {
                return $filterFQN;
            }
        }

        return null;
    }
}
