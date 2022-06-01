<?php

namespace Savks\EFilters\Support\Blocks;

use Closure;
use Savks\EFilters\Blocks\Block;

class RangeBlock extends Block
{
    /**
     * @var int|float
     */
    protected int|float $minValue;

    /**
     * @var int|float
     */
    protected int|float $maxValue;

    /**
     * @var int|float|null
     */
    protected int|null|float $minLimit;

    /**
     * @var int|float|null
     */
    protected int|null|float $maxLimit;

    /**
     * @var Closure(array): array{0: int|float|null, 1: int|float|null}
     */
    protected Closure $limitsResolver;

    /**
     * @var int|float|null
     */
    protected int|float|null $currentMinLimit = null;

    /**
     * @var int|float|null
     */
    protected int|float|null $currentMaxLimit = null;

    /**
     * @param string $id
     * @param string $title
     * @param string $type
     * @param int|float|null $minLimit
     * @param int|float|null $maxLimit
     * @param Closure(array): array{0: int|float|null, 1: int|float|null} $limitsResolver
     */
    public function __construct(
        string $id,
        string $title,
        string $type,
        int|float|null $minLimit,
        int|float|null $maxLimit,
        Closure $limitsResolver
    ) {
        parent::__construct($id, $title, $type);

        $this->minLimit = $minLimit;
        $this->maxLimit = $maxLimit;

        $this->limitsResolver = $limitsResolver;
    }

    /**
     * @param array $aggregated
     */
    public function updateLimits(array $aggregated): void
    {
        [$min, $max] = \call_user_func($this->limitsResolver, $aggregated);

        $this->minValue = $min;
        $this->maxValue = $max;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => 'range',
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
