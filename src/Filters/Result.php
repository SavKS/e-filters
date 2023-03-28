<?php

namespace Savks\EFilters\Filters;

use Savks\EFilters\Blocks\Blocks;
use Savks\ESearch\Builder\Result as ESearchResult;

/**
 * @mixin ESearchResult
 */
class Result
{
    public function __construct(
        protected ESearchResult $result,
        public readonly Blocks $blocks
    ) {
    }

    public function __get(string $name): mixed
    {
        return $this->result->{$name};
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->result->{$name}(...$arguments);
    }

    public function __isset(string $name): bool
    {
        return isset($this->result->{$name});
    }
}
