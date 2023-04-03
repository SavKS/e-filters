<?php

namespace Savks\EFilters\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Savks\EFilters\Blocks\Blocks;
use Savks\EFilters\Filters\Result;
use Savks\ESearch\Builder\Builder;

use Savks\EFilters\Support\Criteria\{
    ChooseCriteria,
    RangeCriteria
};

class Filters
{
    /**
     * @var ChooseCriteria[]|RangeCriteria[]
     */
    protected array $criteria = [];

    public function __construct(protected Builder $query)
    {
    }

    public function choose(ChooseCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    public function range(RangeCriteria $criteria): static
    {
        $this->criteria[] = $criteria;

        return $this;
    }

    /**
     * @param (Closure(EloquentBuilder): void)|null $mapResolver
     */
    public function paginate(
        bool $withMapping = false,
        Closure $mapResolver = null,
        string $pageName = 'page',
        int $page = null
    ): Result {
        $query = $this->query();

        return new Result(
            $query->paginate($withMapping, $mapResolver, $pageName, $page),
            new Blocks(clone $this->query, $this->criteria)
        );
    }

    public function toKibana(bool $pretty = true, int $flags = 0): string
    {
        return $this->query()->toKibana($pretty, $flags);
    }

    public function query(): Builder
    {
        return $this->applyCriteria(clone $this->query);
    }

    /**
     * @param (Closure(EloquentBuilder): void)|null $mapResolver
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
     * @param (Closure(EloquentBuilder): void)|null $mapResolver
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

    public function count(): int
    {
        $query = clone $this->query;

        $this->applyCriteria($query);

        return $query->count();
    }

    protected function applyCriteria(Builder $query): Builder
    {
        foreach ($this->criteria as $criteria) {
            if (! $criteria->exists()) {
                continue;
            }

            if ($criteria instanceof ChooseCriteria) {
                if (! $criteria->conditions->all()) {
                    continue;
                }

                foreach ($criteria->conditions->all() as $condition) {
                    $query->addQuery(
                        $condition->toQuery()
                    );
                }
            } else {
                $query->addQuery(
                    $criteria->condition->toQuery()
                );
            }
        }

        return $query;
    }
}
