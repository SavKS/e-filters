<?php

namespace Savks\EFilters\Support;

use Closure;
use Savks\EFilters\Blocks\Blocks;
use Savks\EFilters\Filters\Result;
use Savks\ESearch\Builder\Builder;

use Elastic\Elasticsearch\Exception\{
    AuthenticationException,
    ClientResponseException,
    ServerResponseException
};
use Savks\EFilters\Support\Criteria\{
    ChooseCriteria,
    RangeCriteria
};

class Filters
{
    /**
     * @var Builder
     */
    protected Builder $query;

    /**
     * @var ChooseCriteria[]|RangeCriteria[]
     */
    protected array $criteria = [];

    /**
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    /**
     * @param ChooseCriteria $criteria
     * @return $this
     */
    public function choose(ChooseCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    /**
     * @param RangeCriteria $criteria
     * @return $this
     */
    public function range(RangeCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    /**
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @param string $pageName
     * @param int|null $page
     * @return Result
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function paginate(
        bool $withMapping = false,
        Closure $mapResolver = null,
        string $pageName = 'page',
        int $page = null
    ): Result {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return new Result(
            $query->paginate($withMapping, $mapResolver, $pageName, $page),
            new Blocks(clone $this->query, $this->criteria)
        );
    }

    /**
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function get(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return new Result(
            $query->get($withMapping, $mapResolver),
            new Blocks(clone $this->query, $this->criteria)
        );
    }

    /**
     * @param bool $withMapping
     * @param Closure|null $mapResolver
     * @return Result
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws AuthenticationException
     */
    public function all(bool $withMapping = false, Closure $mapResolver = null): Result
    {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return new Result(
            $query->all($withMapping, $mapResolver),
            new Blocks(clone $this->query, $this->criteria)
        );
    }

    /**
     * @return int
     * @throws AuthenticationException
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function count(): int
    {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return $query->count();
    }

    /**
     * @param bool $pretty
     * @param int $flags
     * @return string
     */
    public function toKibana(bool $pretty = false, int $flags = 0): string
    {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return $query->toKibana($pretty, $flags);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    protected function applyCriteria(Builder $query): Builder
    {
        foreach ($this->criteria as $criteria) {
            if (! $criteria->exists()) {
                continue;
            }

            if (! $criteria->conditions->all()) {
                continue;
            }

            foreach ($criteria->conditions->all() as $condition) {
                $query->addQuery(
                    $condition->toQuery()
                );
            }
        }

        return $query;
    }
}
