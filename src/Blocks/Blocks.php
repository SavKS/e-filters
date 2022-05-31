<?php

namespace Savks\EFilters\Blocks;

use Illuminate\Support\Arr;
use RuntimeException;
use Savks\EFilters\Builder\Criteria\Condition;
use Savks\EFilters\Builder\Filters\RangeBlock;
use Savks\EFilters\Criteria\Criteria;
use Savks\EFilters\Support\Blocks\ChooseBlock;
use stdClass;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    MissingParameterException,
    ServerResponseException
};
use Savks\ESearch\Builder\{
    DSL\BoolCondition,
    DSL\Query,
    Builder
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
    protected array $criteriaWithBlocks;

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
     * @return RangeBlock[]|ChooseBlock[]
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function fetchCriteriaWithBlocks(): array
    {
        $aggregations = $this->composeAggregations();

        $response = $this->query->client->search($this->query->resource, [
            'from' => 0,
            'size' => 0,
            'body' => [
                'query' => $this->query->toBodyQuery(),
                'aggs' => $aggregations,
            ],
        ]);

        $criteriaWithBlocks = [];

        foreach (\array_keys($aggregations) as $aggregationName) {
            /** @var Criteria $criteria */
            $criteria = Arr::first(
                $this->criteria,
                fn(Criteria $criteria) => $criteria::class === $aggregationName
            );

            $criteriaWithBlocks[] = [
                'criteria' => $criteria,
                'blocks' => $criteria->blockDeclaration()->toBlocks(
                    $response['aggregations'][$criteria::class]
                ),
            ];
        }

        return $this->prepareCriteriaWithBlocks($criteriaWithBlocks);
    }

    /**
     * @param array $blockGroups
     * @return ChooseBlock[]|RangeBlock[]
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function prepareCriteriaWithBlocks(array $blockGroups): array
    {
        $conditions = [];

        foreach ($blockGroups as $blockGroup) {
            /** @var Criteria $criteria */
            $criteria = $blockGroup['criteria'];

            foreach ($criteria->conditions()->all() as $condition) {
                $conditions[$condition->id] = $condition;
            }
        }

        $request = [
            'size' => 0,
            'body' => [
                'query' => $this->query->toBodyQuery(),
            ],
        ];

        foreach ($blockGroups as $blockGroup) {
            foreach ($blockGroup['blocks'] as $block) {
                /** @var ChooseBlock|RangeBlock|Block $block */
                $subQuery = new Query();

                $subQuery->bool(function (BoolCondition $boolCondition) use ($conditions, $block) {
                    if (! ($block instanceof RangeBlock)) {
                        /** @var Condition[] $blockConditions */
                        $blockConditions = Arr::except($conditions, $block->id);

                        foreach ($blockConditions as $condition) {
                            $boolCondition->must($condition->query);
                        }
                    }
                });

                /** @var Criteria $criteria */
                $criteria = $blockGroup['criteria'];

                $request['body']['aggs'][$block->id] = [
                    'filter' => $subQuery->isEmpty() ?
                        [
                            'match_all' => new stdClass(),
                        ] :
                        $subQuery->toArray(),
                    'aggs' => [
                        $block->id => $criteria->blockDeclaration()->aggregations,
                    ],
                ];
            }
        }

        $response = $this->query->client->search($this->query->resource, $request);

        foreach ($blockGroups as $blockGroup) {
            foreach ($blockGroup['blocks'] as $block) {
                /** @var ChooseBlock|RangeBlock|Block $block */
                $block->mapToValues($response['aggregations'][$block->id][$block->id]);
            }
        }

        return $blockGroups;
    }

    /**
     * @param bool $flatten
     * @return array
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     * @throws \Shelter\Kernel\Exceptions\DumpDie
     */
    public function toArray(bool $flatten = false): array
    {
        if (! isset($this->criteriaWithBlocks)) {
            $this->criteriaWithBlocks = $this->fetchCriteriaWithBlocks();
        }

        $blocks = [];
        $selected = [];
        $values = [];

        foreach ($this->criteriaWithBlocks as $criteriaWithBlock) {
            /** @var Criteria $criteria */
            $criteria = $criteriaWithBlock['criteria'];
            /** @var Block[] $blocks */
            $blocks = $criteriaWithBlock['blocks'];

            foreach ($blocks as $block) {
                $blocks[] = $block->toArray($flatten);
            }

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
     * @return array
     */
    protected function composeAggregations(): array
    {
        $result = [];

        foreach ($this->criteria as $criteria) {
            if (! $criteria->blockDeclaration()
                || ! $criteria->blockDeclaration()->aggregations
            ) {
                continue;
            }

            $result[$criteria::class] = $criteria->blockDeclaration()->aggregations;
        }

        return $result;
    }
}
