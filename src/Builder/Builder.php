<?php

namespace Savks\EFilters\Builder;

use Closure;
use Savks\EFilters\Builder\DSL\Query;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Str;

use Elastic\Elasticsearch\{
    Exception\ClientResponseException,
    Exception\MissingParameterException,
    Exception\ServerResponseException,
    Response\Elasticsearch as ElasticsearchResponse,
    Client as ElasticsearchClient
};
use Savks\EFilters\Builder\Criteria\{
    AbstractChooseCriteria,
    AbstractRangeCriteria,
    CriteriaInterface
};
use Savks\EFilters\Builder\Filters\{
    AbstractBlock,
    AbstractChooseFilter,
    AbstractFilter,
    AbstractRangeFilter,
    RangeBlock
};
use Savks\EFilters\Support\{
    AbstractResource,
    SearchParams,
    WithMapping
};
use Illuminate\Pagination\{
    LengthAwarePaginator,
    Paginator
};
use Illuminate\Support\{
    Arr,
    Collection
};

class Builder
{
    /**
     * @var int
     */
    public const PER_PAGE = 12;

    /**
     * @var int
     */
    protected int $itemsLimit = 10000;

    /**
     * @var AbstractResource
     */
    protected AbstractResource $resource;

    /**
     * @var CriteriaInterface[]
     */
    protected array $criteria;

    /**
     * @var array
     */
    protected array $config;

    /**
     * @var ElasticsearchClient
     */
    protected ElasticsearchClient $client;

    /**
     * @var array|null
     */
    protected ?array $currentSortPayload = null;

    /**
     * @var array
     */
    protected array $currentSortIds = [];

    /**
     * @var bool
     */
    protected bool $sortWithScore = false;

    /**
     * @var int
     */
    protected int $currentPageNumber = 1;

    /**
     * @var int
     */
    protected int $perPage;

    /**
     * @var int|null
     */
    protected ?int $skip = null;

    /**
     * @var array|null
     */
    protected ?array $selectedFields = null;

    /**
     * @var array
     */
    protected array $rawQueries = [];

    /**
     * @var AbstractFilter[]
     */
    protected array $filters = [];

    /**
     * @var null|string
     */
    protected ?string $searchTerm = null;

    /**
     * @var bool
     */
    protected bool $isTrackPerformanceEnabled;

    /**
     * @var string|null
     */
    protected ?string $searchQueryFQN = null;

    /**
     * @var array
     */
    protected array $customAggregations = [];

    /**
     * @var bool
     */
    protected bool $skipHits = false;

    /**
     * @param AbstractResource $resource
     */
    public function __construct(AbstractResource $resource)
    {
        $this->resource = $resource;

        $this->client = app('efilter')->getClient();

        $this->perPage = static::PER_PAGE;

        $this->isTrackPerformanceEnabled = (bool)\config('efilter.enable_track_performance');
    }

    /**
     * @return AbstractResource
     */
    public function resource(): AbstractResource
    {
        return $this->resource;
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function select(array $fields): self
    {
        $this->selectedFields = $fields;

        return $this;
    }

    /**e
     * @param string $resource
     * @return Builder
     */
    public static function from(string $resource): Builder
    {
        return new static(
            app('efilter')->resources()->make($resource)
        );
    }

    /**
     * @param array|string|null $data
     * @param array $options
     * @param array|string|null $fallback
     * @param bool $visibleOnly
     * @return $this
     */
    public function sortByWithScore(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): self {
        $this->sortWithScore = true;

        return $this->sortBy($data, $options, $fallback, $visibleOnly);
    }

    /**
     * @param array|string|null $data
     * @param array $options
     * @param array|string|null $fallback
     * @param bool $visibleOnly
     * @return $this
     */
    public function sortBy(
        array|string|null $data,
        array $options = [],
        array|string $fallback = null,
        bool $visibleOnly = true
    ): self {
        $this->currentSortIds = [];

        try {
            if (is_string($data)
                && $this->validateSort($data)
            ) {
                $this->currentSortPayload = $this->resource->config()->sorts->findByIdOrFail($data, $visibleOnly)->toArray($options);

                $this->currentSortIds = [$data];

                return $this;
            }

            if (! is_array($data) || empty($data)) {
                throw new InvalidArgumentException('Invalid sort data type. Must be array or string');
            }

            $sorts = [];

            foreach ($data as $key => $value) {
                $sorts[] = $this->mapSort($key, $value, $visibleOnly);
            }

            $this->currentSortPayload = array_merge(...$sorts);
            $this->currentSortIds = array_values($this->currentSortIds);
        } catch (InvalidArgumentException $e) {
            if ($fallback !== null) {
                return $this->sortBy($fallback, $options, null, $visibleOnly);
            }

            throw $e;
        }

        return $this;
    }

    /**
     * @param int|string $key
     * @param array|string $value
     * @param bool $visibleOnly
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function mapSort(int|string $key, array|string $value, bool $visibleOnly = true): array
    {
        $sorts = [];

        switch (true) {
            case is_int($key) && is_string($value):
                $sorts[] = $this->resource->config()->sorts->findByIdOrFail($value, $visibleOnly)->toArray();

                $this->currentSortIds[] = $value;
                break;

            case is_string($key) && is_array($value):
                $sorts[] = $this->resource->config()->sorts->findByIdOrFail($value, $visibleOnly)->toArray($value);

                $this->currentSortIds[] = $key;
                break;

            case is_int($key) && is_array($value):
                $subSorts = [];

                foreach ($value as $subKey => $subValue) {
                    $subSorts[] = $this->mapSort($subKey, $subValue, $visibleOnly);
                }

                $sorts[] = array_merge(
                    ...array_merge(...$subSorts)
                );

                break;

            default:
                throw new InvalidArgumentException('Invalid sort data');
        }

        return $sorts;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function take(int $count): self
    {
        $this->perPage = $count;

        return $this;
    }

    /**
     * @return $this
     */
    public function takeMax(): static
    {
        $this->take($this->itemsLimit);

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutHits(): self
    {
        $this->skipHits = true;

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function limit(int $count): self
    {
        return $this->take($count);
    }

    /**
     * @param int $count
     * @return $this
     */
    public function skip(int $count): self
    {
        $this->skip = $count;

        return $this;
    }

    /**
     * @param string $id
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    protected function validateSort(string $id): bool
    {
        if (! $this->resource->config()->sorts->findById($id)) {
            throw new InvalidArgumentException("Sort [{$id}] not defined");
        }

        return true;
    }

    /**
     * @param int $number
     * @return $this
     */
    public function setCurrentPageNumber(int $number): self
    {
        $this->currentPageNumber = $number;

        return $this;
    }

    /**
     * @return int
     */
    public function calcOffset(): int
    {
        if ($this->skip) {
            return $this->skip + $this->perPage > $this->itemsLimit ?
                $this->itemsLimit - $this->perPage :
                $this->skip;
        }

        $page = $this->isExceededPageLimit() ?
            $this->lastAllowedPage() :
            $this->currentPageNumber;

        return ($page - 1) * $this->perPage;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setItemsLimit(int $value): Builder
    {
        $this->itemsLimit = $value;

        return $this;
    }

    /**
     * @return bool
     */
    protected function isExceededPageLimit(): bool
    {
        return $this->perPage * $this->currentPageNumber > $this->itemsLimit;
    }

    /**
     * @return float
     */
    protected function lastAllowedPage(): float
    {
        return \floor($this->itemsLimit / $this->perPage);
    }

    /**
     * @param string $name
     * @param array $values
     * @param bool $isInitial
     * @param array|null $payload
     * @return Builder
     *
     * @throws RuntimeException|LogicException
     */
    public function choose(string $name, array $values, bool $isInitial = false, array $payload = null): Builder
    {
        $type = $isInitial ? 'initial' : 'simple';

        if (isset($this->criteria[$type][$name])) {
            throw new LogicException("Criteria with name [{$name}] already applied");
        }

        $criteria = $this->resolveCriteria($name, $payload);

        if (! $criteria instanceof AbstractChooseCriteria) {
            throw new RuntimeException("Not found choose criteria with name [{$name}] ");
        }

        $criteria->use($values);

        $this->criteria[$type][$name] = $criteria;

        return $this;
    }

    /**
     * @param string $name
     * @param array $values
     * @param array $payload
     * @param bool $isInitial
     * @return Builder
     */
    public function chooseWithPayload(string $name, array $values, array $payload, bool $isInitial = false): Builder
    {
        return $this->choose($name, $values, $isInitial, $payload);
    }

    /**
     * @param string $name
     * @param float|int|null $minValue
     * @param float|int|null $maxValue
     * @param bool $isInitial
     * @param array|null $payload
     * @return Builder
     *
     * @throws RuntimeException|LogicException
     */
    public function range(
        string $name,
        float|int|null $minValue = 0,
        float|int $maxValue = null,
        bool $isInitial = false,
        array $payload = null
    ): Builder {
        $type = $isInitial ? 'initial' : 'simple';

        if (isset($this->criteria[$type][$name])) {
            throw new LogicException("Criteria with name [{$name}] already applied");
        }

        if ($minValue === null) {
            $minValue = 0;
        }

        if ($minValue === $maxValue) {
            ++$maxValue;
        }

        $criteria = $this->resolveCriteria($name, $payload);

        if (! $criteria instanceof AbstractRangeCriteria) {
            throw new RuntimeException("Not found range criteria with name [{$name}] ");
        }

        $criteria->use($minValue, $maxValue);

        $this->criteria[$type][$name] = $criteria;

        return $this;
    }


    /**
     * @param string $name
     * @param array $payload
     * @param float|int|null $minValue
     * @param float|int|null $maxValue
     * @param bool $isInitial
     * @return Builder
     *
     * @throws RuntimeException|LogicException
     */
    public function rangeWithPayload(
        string $name,
        array $payload,
        float|int|null $minValue = 0,
        float|int $maxValue = null,
        bool $isInitial = false
    ): Builder {
        return $this->range($name, $minValue, $maxValue, $isInitial, $payload);
    }

    /**
     * @param string $id
     * @param array|null $payload
     * @return CriteriaInterface
     *
     * @throws RuntimeException
     */
    protected function resolveCriteria(string $id, array $payload = null): CriteriaInterface
    {
        $criterion = $this->resource->config()->criteria->findById($id);

        if (! $criterion) {
            throw new RuntimeException("Criteria [{$id}] not defined");
        }

        return new $criterion($payload);
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function initialQuery(Closure $callback): self
    {
        $callback(
            new InitialQuery($this)
        );

        return $this;
    }

    /**
     * @param array $filters
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function withFilters(array $filters): self
    {
        foreach ($filters as $index => $name) {
            $payload = [];

            if (\is_array($name)) {
                $payload = $name;
                $name = $index;
            }

            if (array_key_exists($name, $this->filters)) {
                throw new InvalidArgumentException("Filter with name [{$name}] already added");
            }

            $this->filters[$name] = [
                'handlers' => $this->resolveFilter($name),
                'payload' => $payload,
            ];
        }

        return $this;
    }

    /**
     * @param string $id
     * @return class-string<AbstractFilter>|null
     * @throws RuntimeException
     */
    protected function resolveFilter(string $id): ?string
    {
        $data = $this->resource->config()->filters->findById($id);

        if ($data) {
            return $data;
        }

        throw new RuntimeException("Filter with name [{$id}] not defined");
    }

    /**
     * @param array $query
     * @param bool $isInitial
     * @return $this
     */
    public function addRawQuery(array $query, bool $isInitial = false): self
    {
        $this->rawQueries[$isInitial ? 'initial' : 'simple'][] = $query;

        return $this;
    }

    /**
     * @param Query|callable $predicate
     * @param bool $isInitial
     * @return $this
     */
    public function addDSLQuery(Query|callable $predicate, bool $isInitial = false): self
    {
        if ($predicate instanceof Query) {
            $query = $predicate;
        } else {
            $query = new Query();

            $predicate($query);
        }

        if (! $query->isEmpty()) {
            $this->rawQueries[$isInitial ? 'initial' : 'simple'][] = $query->toArray();
        }

        return $this;
    }

    /**
     * @param string $type
     * @return array
     */
    protected function buildQueryByType(string $type): array
    {
        $criteria = $this->criteria[$type] ?? [];
        $rawQueries = $this->rawQueries[$type] ?? [];

        $result = [];

        foreach ($criteria as $criterion) {
            /** @var AbstractChooseCriteria|AbstractRangeCriteria $criterion */
            foreach ($criterion->conditions as $condition) {
                $result[] = [
                    'bool' => [
                        'should' => $condition->query,
                    ],
                ];
            }
        }

        foreach ($rawQueries as $rawQuery) {
            $result[] = $rawQuery;
        }

        return $result;
    }

    /**
     * @param bool $initial
     * @return array
     */
    protected function buildQuery(bool $initial = false): array
    {
        if (! $initial) {
            $query = \array_merge(
                $this->buildQueryByType('initial'),
                $this->buildQueryByType('simple')
            );
        } else {
            $query = $this->buildQueryByType('initial');
        }

        return [
            'bool' => [
                'must' => $query,
            ],
        ];
    }

    /**
     * @param array $query
     * @param array $aggregations
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function fetchFiltersByQuery(array $query, array $aggregations): array
    {
        return $this->client->search([
            '_source' => $this->resource->key(),
            'index' => $this->resource->prepareIndexName(),
            'from' => 0,
            'size' => 0,
            'body' => [
                'query' => $query,
                'aggs' => $aggregations,
            ],
        ]);
    }

    /**
     * @return array
     */
    protected function composeAggregations(): array
    {
        $result = [];

        foreach ($this->filters as $name => $data) {
            $result[] = [
                'name' => $name,
                'aggregations' => $data['handlers']::aggregations(),
            ];
        }

        return \array_merge($result);
    }

    /**
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    protected function fetchFilters(): array
    {
        $aggregationGroups = $this->composeAggregations();
        $query = $this->buildQuery(true);

        $result = $this->client->search([
            'index' => $this->resource->prepareIndexName(),
            'from' => 0,
            'size' => 0,
            'body' => [
                'query' => $query,
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
     * @param bool $asJson
     * @param int $flags
     * @return array|string
     */
    public function query(bool $asJson = false, int $flags = 0): array|string
    {
        $query = $this->finalQuery();

        return $asJson ? \json_encode($query, $flags) : $query;
    }

    /**
     * @param bool $pretty
     * @param int $flags
     * @return string
     */
    public function queryForKibana(bool $pretty = false, int $flags = 0): string
    {
        $result = [
            "POST {$this->resource->prepareIndexName()}/_search",
        ];

        $query = $this->query();

        $result[] = \json_encode(
            \array_merge(
                [
                    'from' => $query['from'],
                    'size' => $query['size'],
                ],
                $query['body']
            ),
            \JSON_UNESCAPED_UNICODE | ($pretty ? \JSON_PRETTY_PRINT : 0) | $flags
        );

        return \implode("\n", $result);
    }

    /**
     * @return array
     */
    public function availableSortIds(): array
    {
        $result = [];

        foreach ($this->resource->config()->sorts->all() as $sort) {
            $result[] = $sort->id;
        }

        return $result;
    }

    /**
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function exec(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $queryUniqId = Str::random();

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("Efilter: Search in \"{$this->resource()::id()}\"", [
                'name' => $queryUniqId,
            ])->begin();
        }

        $rawResult = $this->prepareResult(
            $this->client->search(
                $this->finalQuery()
            )
        );

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("Efilter: Search in \"{$this->resource()::id()}\"", [
                'name' => $queryUniqId,
            ])->end();
        }

        $sortedBy = [];

        foreach ($this->currentSortIds as $sortId) {
            $sortedBy[] = $this->resource->config()->sorts->findByIdOrFail($sortId);
        }

        $items = $this->prepareItems($rawResult, $withMapping, $mapResolver);

        if (! $this->skip && ! $this->skipHits) {
            $paginator = $this->makePaginator($items, $rawResult['hits']['total']['value']);
        } else {
            $paginator = null;
        }

        $result = new Result(
            $rawResult,
            $items,
            $paginator,
            $this->resource,
            $sortedBy,
            $this->searchTerm
        );

        $result->filters = $this->filters ? $this->fetchFilters() : [];

        return $result;
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function chunkBy(
        string $field,
        int $limit,
        Closure $callback,
        bool $withMapping = false,
        Closure $mapResolver = null
    ): void {
        $done = false;

        $lastField = $field === '_id' ? $field : "_source.{$field}";
        $lastValue = null;

        while (! $done) {
            $finalQuery = $this->finalQuery();

            $finalQuery['size'] = $limit;

            if ($lastValue) {
                $finalQuery['body']['query'] = [
                    'bool' => [
                        'must' => [
                            $finalQuery['body']['query'],

                            [
                                'range' => [
                                    $field => [
                                        'gt' => $lastValue,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $finalQuery['body']['sort'] = [
                $field => [
                    'order' => 'asc',
                ],
            ];

            $rawResult = $this->prepareResult(
                $this->client->search($finalQuery)
            );

            $count = \count($rawResult['hits']['hits']);

            if ($count === 0) {
                break;
            }

            $done = $count < $limit;

            $lastValue = Arr::get(
                last($rawResult['hits']['hits']),
                $lastField
            );

            $result = new Result(
                $rawResult,
                $this->prepareItems($rawResult, $withMapping, $mapResolver),
                null,
                $this->resource,
                [],
                $this->searchTerm
            );

            if ($callback($result) === false) {
                break;
            }
        }
    }

    /**
     * @param string $field
     * @param int $limit
     * @param Closure $callback
     * @param Closure $mapResolver
     * @return void
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function chunkByWithMapping(
        string $field,
        int $limit,
        Closure $callback,
        Closure $mapResolver
    ): void {
        $this->chunkBy(
            $field,
            $limit,
            $callback,
            true,
            $mapResolver
        );
    }

    /**
     * @return int
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function count(): int
    {
        $queryUniqId = Str::random();

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("Efilter: Count from \"{$this->resource()::id()}\"", [
                'name' => $queryUniqId,
            ])->begin();
        }

        $result = $this->client->count(
            $this->countQuery()
        );

        if ($this->isTrackPerformanceEnabled && \function_exists('clock')) {
            \clock()->event("Efilter: Count from \"{$this->resource()::id()}\"", [
                'name' => $queryUniqId,
            ])->end();
        }

        return $result['count'];
    }

    /**
     * @return array
     */
    protected function finalQuery(): array
    {
        $query = $this->buildQuery();

        $result = [
            'index' => $this->resource->prepareIndexName(),
            'from' => $this->skipHits ? 0 : $this->calcOffset(),
            'size' => $this->skipHits ? 0 : $this->perPage,
            'body' => [
                'query' => $query,
            ],
        ];

        if ($this->customAggregations) {
            $result['body']['aggs'] = [];

            foreach ($this->customAggregations as $customAggregationName => $customAggregationData) {
                $result['body']['aggs'][$customAggregationName] = $customAggregationData;
            }
        }

        if ($this->selectedFields) {
            $result['_source'] = $this->selectedFields;
        }

        if ($this->currentSortPayload) {
            if (Arr::isAssoc($this->currentSortPayload)) {
                $scoreSort = [
                    '_score' => [
                        'order' => 'desc',
                    ],
                ];
            } else {
                $scoreSort = [
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ],
                ];
            }

            $result['body']['sort'] = $this->sortWithScore ?
                \array_merge(
                    $scoreSort,
                    $this->currentSortPayload
                ) :
                $this->currentSortPayload;
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function countQuery(): array
    {
        return [
            'index' => $this->resource->prepareIndexName(),
            'body' => [
                'query' => $this->buildQuery(),
            ],
        ];
    }

    /**
     * @param $items
     * @param int $total
     * @return LengthAwarePaginator
     */
    protected function makePaginator($items, int $total): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $this->currentPageNumber,
            [
                'path' => Paginator::resolveCurrentPath(),
            ]
        );
    }

    /**
     * @param ElasticsearchResponse $response
     * @return array
     */
    protected function prepareResult(ElasticsearchResponse $response): array
    {
        $result = $response->asArray();

        $result['hits']['total']['value'] = \min(
            $result['hits']['total']['value'],
            $this->maxAllowedItems()
        );

        if (! ($this->currentPageNumber <= $this->lastAllowedPage())) {
            $result['hits']['hits']['value'] = $this->itemsLimit;
        }

        return $result;
    }

    /**
     * @return int
     */
    protected function maxAllowedItems(): int
    {
        return (int)(($this->lastAllowedPage() - 1) * $this->perPage + $this->perPage);
    }

    /**
     * @param mixed|string $term
     * @param array $fields
     * @param array|SearchParams $params
     * @return $this
     */
    public function search(mixed $term, array $fields, SearchParams|array $params = []): self
    {
        $isValidString = \is_string($term) && \json_encode($term) !== false;

        if (empty($isValidString)) {
            return $this;
        }

        $searchQueryClass = $this->searchQueryFQN ?: $this->resource()->searchQueryClass();

        /** @var DefaultSearchQuery $searchQueryBuilder */
        $searchQueryBuilder = new $searchQueryClass(
            $term,
            $fields,
            \is_array($params) ? SearchParams::fromArray($params) : $params
        );

        $this->searchTerm = $searchQueryBuilder->term();

        $this->addRawQuery(
            $searchQueryBuilder->toQuery(),
            true
        );

        return $this;
    }

    /**
     * @param string $name
     * @param ...$args
     * @return Builder
     */
    public function applyScope(string $name, ...$args): Builder
    {
        $scopeCallback = $this->resource()->config()->queryScopes->findByName($name);

        if (! $scopeCallback) {
            throw new RuntimeException("Scope with name \"{$name}\" not defined");
        }

        return $scopeCallback($this, ...$args);
    }

    /**
     * @param string $searchQueryFQN
     * @return $this
     */
    public function setSearchQueryClass(string $searchQueryFQN): Builder
    {
        $this->searchQueryFQN = $searchQueryFQN;

        return $this;
    }

    /**
     * @param string $name
     * @param array $data
     * @return $this
     */
    public function addCustomAggregation(string $name, array $data): Builder
    {
        $this->customAggregations[$name] = $data;

        return $this;
    }

    /**
     * @param array $rawResult
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Collection
     */
    protected function prepareItems(array $rawResult, bool $withMapping, ?Closure $mapResolver): Collection
    {
        $items = [];

        if ($withMapping) {
            $isResolverCalled = $mapResolver === null;

            if (! $this->resource instanceof WithMapping) {
                throw new RuntimeException(
                    sprintf(
                        'Resource "%s" must implement "%s"',
                        $this->resource->id(),
                        WithMapping::class
                    )
                );
            }

            $items = $this->resource->mapTo(
                $rawResult,
                $mapResolver !== null ?
                    function (...$args) use (&$isResolverCalled, $mapResolver) {
                        $mapResolver(...$args);

                        $isResolverCalled = true;
                    } :
                    null
            );

            if (! $isResolverCalled) {
                throw new LogicException('Map resolver was passed but not called');
            }
        } else {
            foreach ($rawResult['hits']['hits'] ?? [] as &$hit) {
                $items[] = $hit['_source'];
            }
        }

        return \collect($items);
    }
}
