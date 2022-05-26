<?php

namespace Savks\EFilters\Support;

use Closure;

use Savks\EFilters\Support\Config\{
    CriteriaRepository,
    FiltersRepository,
    QueryScopesRepository,
    SortsRepository
};

class Config
{
    /**
     * @var CriteriaRepository
     */
    public CriteriaRepository $criteria;

    /**
     * @var SortsRepository
     */
    public SortsRepository $sorts;

    /**
     * @var FiltersRepository
     */
    public FiltersRepository $filters;

    /**
     * @var QueryScopesRepository
     */
    public QueryScopesRepository $queryScopes;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->criteria = new CriteriaRepository();
        $this->sorts = new SortsRepository();
        $this->filters = new FiltersRepository();
        $this->queryScopes = new QueryScopesRepository();
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineCriteria(Closure $handler): static
    {
        $handler($this->criteria);

        return $this;
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineSorts(Closure $handler): static
    {
        $handler($this->sorts);

        return $this;
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineFilters(Closure $handler): static
    {
        $handler($this->filters);

        return $this;
    }

    /**
     * @param Closure $handler
     * @return static
     */
    public function defineQueryScopes(Closure $handler): static
    {
        $handler($this->queryScopes);

        return $this;
    }
}
