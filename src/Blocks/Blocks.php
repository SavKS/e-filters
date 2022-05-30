<?php

namespace Savks\EFilters\Blocks;

use Illuminate\Support\Arr;
use LogicException;
use RuntimeException;
use Savks\EFilters\Criteria\Criteria;
use Savks\ESearch\Builder\Builder;

use Elastic\Elasticsearch\Exception\{
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};

class Blocks
{
    /**
     * @var Builder
     */
    protected Builder $query;

    /**
     * @var Criteria[]
     */
    protected array $criteria;

    /**
     * @var array
     */
    protected array $blocksData;

    /**
     * @param Builder $query
     * @param array $criteria
     */
    public function __construct(Builder $query, array $criteria)
    {
        $this->query = $query;
        $this->criteria = $criteria;
    }

    /**
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function fetchBlocksData(): array
    {
        $aggregationGroups = $this->composeAggregations();

        $result = $this->query->client->search([
            'index' => $this->query->resource->prefixedIndexName(),
            'from' => 0,
            'size' => 0,
            'body' => [
                'query' => $this->query->toBodyQuery(),
                'aggs' => Arr::collapse(
                    Arr::pluck($aggregationGroups, 'aggregations')
                ),
            ],
        ]);

        $filters = [];

        foreach ($aggregationGroups as $aggregationGroup) {
            $data = $this->filters[$aggregationGroup['name']];

            $aggregated = Arr::only(
                $result['aggregations'],
                \array_keys(
                    $data['handlers']::aggregations()
                )
            );

            /** @var AbstractChooseFilter|AbstractRangeFilter $filter */
            $filters[] = $filter = new $data['handlers']($aggregated, $data['payload']);

            if (! isset($this->criteria['simple'][$filter->id()])) {
                throw new LogicException("Criteria for [{$filter->id()}] not defined");
            }

            $filter->setCriteria(
                $this->criteria['simple'][$filter->id()]
            );
        }

        return $this->prepareFilters($filters);
    }

    /**
     * @param array $filters
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function prepareFilters(array $filters): array
    {
        $initialQuery = $this->buildQueryByType('initial');

        $criteria = $this->criteria['simple'] ?? [];

        $rawQueries = [];

        foreach ($this->rawQueries['simple'] ?? [] as $rawQuery) {
            $rawQueries[] = $rawQuery;
        }

        $blocks = collect($filters)
            ->pluck('blocks')
            ->collapse()
            ->keyBy('id');

        $conditions = collect($criteria)
            ->pluck('conditions')
            ->collapse()
            ->keyBy('id');

        $preQuery = [
            'index' => $this->resource->prepareIndexName(),
        ];

        $queries = [];

        foreach ($blocks as $block) {
            /** @var AbstractBlock $block */
            $blockConditions = $conditions->except($block->id);

            $subQuery = $rawQueries;

            $subQuery[] = [
                'bool' => [
                    'must' => $initialQuery,
                ],
            ];

            if (! ($block instanceof RangeBlock)) {
                foreach ($blockConditions as $condition) {
                    $subQuery[] = [
                        'bool' => [
                            'should' => $condition->query,
                        ],
                    ];
                }
            }

            $queries[] = $preQuery;
            $queries[] = [
                'query' => [
                    'bool' => [
                        'must' => $subQuery,
                    ],
                ],
                'size' => 0,
                'aggs' => $this->filters[$block->entity]['handlers']::aggregations(),
            ];
        }

        if ($queries) {
            $result = $this->client->msearch([
                'body' => $queries,
            ]);
        } else {
            $result = [];
        }

        $i = 0;

        foreach ($blocks as $block) {
            $block->mapToValues(
                $result['responses'][$i++]['aggregations'] ?? []
            );
        }

        return $filters;
    }

    /**
     * @param bool $flatten
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function toArray(bool $flatten = false): array
    {
        if (! isset($this->blocksData)) {
            $this->fetchBlocksData();
        }

        dde(1);

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
}
