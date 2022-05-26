<?php

namespace Savks\EFilters\Builder\Criteria;

use Illuminate\Support\Arr;

abstract class AbstractRangeCriteria implements CriteriaInterface
{
    /**
     * @var array|null
     */
    public ?array $payload;

    /**
     * @var int|float
     */
    public int|float $minValue;

    /**
     * @var int|float
     */
    public int|float $maxValue;

    /**
     * @var Condition[]
     */
    public array $conditions = [];

    /**
     * @param array|null $payload
     * @return void
     */
    public function __construct(array $payload = null)
    {
        $this->payload = $payload;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    protected function payload(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->payload;
        }

        return Arr::get($this->payload, $key, $default);
    }

    /**
     * @param float|int|null $minValue
     * @param float|int|null $maxValue
     * @return AbstractRangeCriteria
     */
    public function use(float|int $minValue = null, float|int $maxValue = null): AbstractRangeCriteria
    {
        if ($minValue === null && $maxValue === null) {
            throw new \InvalidArgumentException('Specify a minimum or maximum value');
        }

        $this->minValue = $minValue;
        $this->maxValue = $maxValue;

        $this->conditions[] = $this->prepare();

        return $this;
    }

    /**
     * @return Condition
     */
    abstract protected function prepare(): Condition;
}
