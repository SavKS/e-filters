<?php

namespace Savks\EFilters\Support\Blocks;

use Closure;
use Savks\EFilters\Blocks\Block;

class RangeBlock extends Block
{
    protected int|float $minValue;

    protected int|float $maxValue;

    protected int|float|null $currentMinLimit = null;

    protected int|float|null $currentMaxLimit = null;

    /**
     * @param Closure(array): array{0: int|float|null, 1: int|float|null} $limitsResolver
     */
    public function __construct(
        string $id,
        string $title,
        string $entityType,
        protected int|float|null $minLimit,
        protected int|float|null $maxLimit,
        protected Closure $limitsResolver
    ) {
        parent::__construct($id, $title, $entityType);
    }

    public function updateLimits(array $aggregated): void
    {
        [$min, $max] = \call_user_func($this->limitsResolver, $aggregated);

        $this->minValue = $min;
        $this->maxValue = $max;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'range',
            'entityType' => $this->entityType,
            'title' => $this->title,
            'payload' => $this->payload,
            'limits' => [
                'min' => [
                    'last' => $this->minLimit,
                    'current' => $this->currentMinLimit,
                ],
                'max' => [
                    'last' => $this->maxLimit,
                    'current' => $this->currentMaxLimit,
                ],
            ],
            'weight' => $this->weight,
        ];
    }
}
