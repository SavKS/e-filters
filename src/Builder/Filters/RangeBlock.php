<?php

namespace Savks\EFilters\Builder\Filters;

use Illuminate\Support\Arr;

class RangeBlock extends AbstractBlock
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
     * @var int|float
     */
    protected int|float $currentMinValue;

    /**
     * @var int|float
     */
    protected int|float $currentMaxValue;

    /**
     * @param float|int $minValue
     * @param float|int $maxValue
     * @return $this
     */
    public function setValues(float|int $minValue, float|int $maxValue): self
    {
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;

        return $this;
    }

    /**
     * @param array $aggregated
     */
    public function mapToValues(array $aggregated): void
    {
        [$min, $max] = \is_string($this->mapper) ?
            Arr::get($aggregated, $this->mapper) :
            \call_user_func($this->mapper, $aggregated);

        $this->minValue = $min;
        $this->maxValue = $max;
    }

    /**
     * @param bool $flatten
     * @return array
     */
    public function toArray(bool $flatten = false): array
    {
        return [
            'block' => [
                'id' => $this->id,
                'type' => 'range',
                'name' => $this->name,
                'payload' => $this->payload,
                'fields' => [
                    'min' => [
                        'last' => $this->minValue,
                        'current' => $this->currentMinValue,
                    ],
                    'max' => [
                        'last' => $this->maxValue,
                        'current' => $this->currentMaxValue,
                    ],
                ],
                'weight' => $this->weight,
            ],
        ];
    }
}
