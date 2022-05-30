<?php

namespace Savks\EFilters\Criteria;

use Savks\ESearch\Builder\DSL\Query;

class Conditions
{
    /**
     * @var Condition[]
     */
    protected array $conditions;

    /**
     * @return Condition[]
     */
    public function all(): array
    {
        return \array_values($this->conditions);
    }

    /**
     * @param string $id
     * @param callable|Query $predicate
     * @return $this
     */
    public function add(string $id, callable|Query $predicate): static
    {
        if (\is_callable($predicate)) {
            $query = new Query();

            $predicate($query);
        } else {
            $query = $predicate;
        }

        $this->conditions[$id] = new Condition($id, $query);

        return $this;
    }
}
