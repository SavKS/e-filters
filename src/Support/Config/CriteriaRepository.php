<?php

namespace Savks\EFilters\Support\Config;

use Savks\EFilters\Builder\Criteria\CriteriaInterface;
use RuntimeException;

class CriteriaRepository
{
    /**
     * @var array<int, class-string<CriteriaInterface>>
     */
    protected array $criteriaFQNs = [];

    /**
     * @return string[]
     */
    public function all(): array
    {
        return $this->criteriaFQNs;
    }

    /**
     * @param string $criterionFQN
     * @return $this
     */
    public function add(string $criterionFQN): self
    {
        if (! is_subclass_of($criterionFQN, CriteriaInterface::class)) {
            throw new RuntimeException("Criterion [$criterionFQN] must implement " . CriteriaInterface::class);
        }

        $this->criteriaFQNs[] = $criterionFQN;

        return $this;
    }

    /**
     * @param string $id
     * @return class-string<CriteriaInterface>|null
     */
    public function findById(string $id): ?string
    {
        foreach ($this->all() as $criteriaFQN) {
            if ($criteriaFQN::id() === $id) {
                return $criteriaFQN;
            }
        }

        return null;
    }
}
