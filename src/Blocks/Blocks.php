<?php

namespace Savks\EFilters\Blocks;

use Illuminate\Support\Arr;
use Savks\EFilters\Criteria\Condition;
use stdClass;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    ServerResponseException
};
use Savks\EFilters\Support\Blocks\{
    ChooseBlock,
    RangeBlock
};
use Savks\EFilters\Support\Criteria\{
    ChooseCriteria,
    RangeCriteria
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
     * @var ChooseCriteria[]|RangeCriteria[]
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
     * @param bool $flatten
     * @return array
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function toArray(bool $flatten = false): array
    {
        if (! isset($this->criteriaWithBlocks)) {
            $this->criteriaWithBlocks = $this->fetchCriteriaWithBlocks();
        }

        $mappedBlocks = [];
        $selected = [];
        $mappedValues = [];

        foreach ($this->criteriaWithBlocks as $criteriaWithBlock) {
            /** @var ChooseCriteria|RangeCriteria $criteria */
            $criteria = $criteriaWithBlock['criteria'];

            if ($criteria instanceof ChooseCriteria) {
                foreach ($criteriaWithBlock['blocks'] as $block) {
                    /** @var ChooseBlock $block */
                    if ($flatten) {
                        [$mappedBlock, $mapperBlockValues] = $block->toArray(true);

                        if (! $mappedBlock['valueIds']) {
                            continue;
                        }

                        $mappedValues[$block->id] = $mapperBlockValues;
                    } else {
                        $mappedBlock = $block->toArray();

                        if (! $mappedBlock['values']) {
                            continue;
                        }
                    }

                    $mappedBlocks[] = $mappedBlock;

                    $condition = $criteria->conditions->tryFindByBlockId($block->id);

                    if ($condition?->values) {
                        $selected[$block->id] = $condition->values;
                    }
                }
            } else {
                foreach ($criteriaWithBlock['blocks'] as $block) {
                    /** @var RangeBlock $block */
                    $mappedBlocks[] = $block->toArray();

                    $selected[$block->id] = [
                        'min' => $criteria->condition->minValue,
                        'max' => $criteria->condition->maxValue,
                    ];
                }
            }
        }

        $sortedMappedBlocks = \collect($mappedBlocks)->sortBy(
            fn (array $mappedBlock) => $mappedBlock['weight'] ?? $mappedBlock['title']
        )->values()->all();

        if ($flatten) {
            return [
                'blocks' => $sortedMappedBlocks,
                'values' => $mappedValues,
                'selected' => $selected,
            ];
        }

        return [
            'blocks' => $sortedMappedBlocks,
            'selected' => $selected,
        ];
    }

    /**
     * @return RangeBlock[]|ChooseBlock[]
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function fetchCriteriaWithBlocks(): array
    {
        $aggregations = $this->collectAggregations();

        $response = $this->query->client->search($this->query->resource, [
            'from' => 0,
            'size' => 0,
            'body' => [
                'query' => $this->query->toBodyQuery(),
                'aggs' => $aggregations ?: new stdClass(),
            ],
        ]);

        $criteriaWithBlocks = [];

        foreach (\array_keys($aggregations) as $aggregationName) {
            /** @var ChooseCriteria|RangeCriteria $criteria */
            $criteria = Arr::first(
                $this->criteria,
                fn (ChooseCriteria|RangeCriteria $criteria) => $criteria::class === $aggregationName
            );

            $criteriaWithBlocks[] = [
                'criteria' => $criteria,
                'blocks' => $criteria->blockDeclaration->toBlocks(
                    $response['aggregations'][$criteria::class]
                ),
            ];
        }

        return $this->updateCriteriaWithBlocksCounts($criteriaWithBlocks);
    }

    /**
     * @return array
     */
    protected function collectAggregations(): array
    {
        $result = [];

        foreach ($this->criteria as $criteria) {
            if (! $criteria->blockDeclaration
                || ! $criteria->blockDeclaration->aggregations
            ) {
                continue;
            }

            $result[$criteria::class] = $criteria->blockDeclaration->aggregations;
        }

        return $result;
    }

    /**
     * @param array $blockGroups
     * @return ChooseBlock[]|RangeBlock[]
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    protected function updateCriteriaWithBlocksCounts(array $blockGroups): array
    {
        $conditions = [];

        foreach ($blockGroups as $blockGroup) {
            /** @var ChooseCriteria|RangeCriteria $criteria */
            $criteria = $blockGroup['criteria'];

            if ($criteria instanceof ChooseCriteria) {
                foreach ($criteria->conditions->all() as $condition) {
                    $conditions[$condition->blockId] = $condition;
                }
            } else {
                $conditions[$criteria->condition->blockId] = $criteria->condition;
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
                /** @var ChooseBlock|RangeBlock $block */
                $subQuery = new Query();

                $subQuery->bool(function (BoolCondition $boolCondition) use ($conditions, $block) {
                    if (! ($block instanceof RangeBlock)) {
                        /** @var Condition[] $blockConditions */
                        $blockConditions = Arr::except($conditions, $block->id);

                        foreach ($blockConditions as $condition) {
                            $boolCondition->must(
                                $condition->toQuery()
                            );
                        }
                    }
                });

                /** @var ChooseCriteria|RangeCriteria $criteria */
                $criteria = $blockGroup['criteria'];

                $request['body']['aggs'][$block->id] = [
                    'filter' => $subQuery->isEmpty() ?
                        [
                            'match_all' => new stdClass(),
                        ] :
                        $subQuery->toArray(),
                    'aggs' => [
                        $block->id => $criteria->blockDeclaration->aggregations,
                    ],
                ];
            }
        }

        $response = $this->query->client->search($this->query->resource, $request);

        foreach ($blockGroups as $blockGroup) {
            foreach ($blockGroup['blocks'] as $block) {
                if ($block instanceof ChooseBlock) {
                    $block->updateValueCounts($response['aggregations'][$block->id][$block->id]);
                } else {
                    $block->updateLimits($response['aggregations'][$block->id][$block->id]);
                }
            }
        }

        return $blockGroups;
    }
}
