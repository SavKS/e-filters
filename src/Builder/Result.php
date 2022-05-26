<?php

namespace Savks\EFilters\Builder;

use Savks\EFilters\Builder\Filters\AbstractFilter;
use Savks\EFilters\Support\AbstractResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

class Result
{
    /**
     * @var array
     */
    public array $data;

    /**
     * @var Collection
     */
    public Collection $items;

    /**
     * @var LengthAwarePaginator|null
     */
    public ?LengthAwarePaginator $paginator;

    /**
     * @var string|null
     */
    public ?string $searchTerm;

    /**
     * @var AbstractResource
     */
    protected AbstractResource $resource;

    /**
     * @var AbstractFilter[]
     */
    public array $filters;

    /**
     * @var Sort[]
     */
    public array $sortedBy;

    /**
     * @param array $data
     * @param Collection $items
     * @param LengthAwarePaginator|null $paginator
     * @param AbstractResource $resource
     * @param array $sortedBy
     * @param string|null $searchTerm
     */
    public function __construct(
        array $data,
        Collection $items,
        ?LengthAwarePaginator $paginator,
        AbstractResource $resource,
        array $sortedBy = [],
        string $searchTerm = null
    ) {
        $this->data = $data;
        $this->items = $items;
        $this->paginator = $paginator;
        $this->resource = $resource;
        $this->sortedBy = $sortedBy;
        $this->searchTerm = $searchTerm;
    }

    /**
     * @return AbstractResource
     */
    public function getResource(): AbstractResource
    {
        return $this->resource;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data['hits']['hits']);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * @return array
     */
    public function ids(): array
    {
        return $this->items->pluck(
            $this->resource->key()
        )->all();
    }

    /**
     * @param bool $flatten
     * @return array
     */
    public function mapFilters(bool $flatten = false): array
    {
        $blocks = [];
        $selected = [];
        $values = [];

        foreach ($this->filters as $filter) {
            $data = $filter->toArray($flatten);

            if (! empty($data['blocks'])) {
                $blocks[] = $data['blocks'];
            }

            if (! empty($data['selected'])) {
                $selected[] = $data['selected'];
            }

            if (! empty($data['values'])) {
                $values[] = $data['values'];
            }
        }

        $data = [
            'blocks' => \collect($blocks ? \array_merge(...$blocks) : [])
                ->sortBy('weight')
                ->values()
                ->all(),
            'selected' => $selected ? \array_merge(...$selected) : [],
        ];

        if ($flatten) {
            $data['values'] = $values ? \array_merge(...$values) : [];
        }

        return $data;
    }

    /**
     * @param string $id
     * @param bool $flatten
     * @return array
     */
    public function mapFilter(string $id, bool $flatten = false): array
    {
        $matchedFilter = null;

        foreach ($this->filters as $filter) {
            if ($filter->id() === $id) {
                $matchedFilter = $filter;
            }
        }

        if (! $matchedFilter) {
            throw new RuntimeException("Filter [{$id}] not defined");
        }

        return $matchedFilter->toArray($flatten);
    }

    /**
     * @return Sort|null
     */
    public function firstSort(): ?Sort
    {
        return $this->sortedBy ? head($this->sortedBy) : null;
    }

    /**
     * @return int|null
     */
    public function total(): ?int
    {
        return $this->data['hits']['total']['value'] ?: null;
    }

    /**
     * @return array
     */
    public function hits(): array
    {
        return $this->data['hits']['hits'];
    }

    /**
     * @return array
     */
    public function customAggregations(): array
    {
        return $this->data['aggregations'] ?? [];
    }

    /**
     * @param string $name
     * @return array|null
     */
    public function customAggregation(string $name): ?array
    {
        return $this->data['aggregations'][$name] ?? null;
    }
}
