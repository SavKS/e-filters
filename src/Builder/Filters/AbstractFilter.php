<?php

namespace Savks\EFilters\Builder\Filters;

use Savks\EFilters\Builder\Criteria\CriteriaInterface;
use Illuminate\Support\Arr;

abstract class AbstractFilter
{
    /**
     * @var array
     */
    public array $aggregated;

    /**
     * @var array
     */
    public array $blocks;

    /**
     * @var CriteriaInterface|null
     */
    public ?CriteriaInterface $criteria;

    /**
     * @var array
     */
    public array $payload;

    /**
     * Price constructor.
     * @param array $aggregated
     * @param array $payload
     */
    public function __construct(array $aggregated, array $payload)
    {
        $this->aggregated = $aggregated;
        $this->payload = $payload;

        $this->blocks = $this->build();
    }

    /**
     * @return string
     */
    abstract public static function id(): string;

    /**
     * @return array
     */
    abstract public function build(): array;

    /**
     * @return array
     */
    abstract public function selected(): array;

    /**
     * @return array
     */
    abstract public static function aggregations(): array;

    /**
     * @param bool $flatten
     * @return array
     */
    public function toArray(bool $flatten = false): array
    {
        $result = [];

        foreach ($this->blocks as $block) {
            /** @var AbstractBlock $block */
            $result[] = $block->toArray($flatten);
        }

        $blocks = \array_map(
            function (array $block) {
                $block['entity'] = $this->id();

                return $block;
            },
            Arr::pluck($result, 'block')
        );

        $selected = [
            $this->id() => $this->selected(),
        ];

        if (! $flatten) {
            return [
                'blocks' => $blocks,
                'selected' => $selected,
            ];
        }

        $values = Arr::pluck($result, 'values');

        return [
            'blocks' => $blocks,
            'values' => $values ? \array_merge(...$values) : [],
            'selected' => $selected,
        ];
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
}
