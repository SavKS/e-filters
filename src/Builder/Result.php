<?php

namespace Savks\EFilters\Builder;

use Savks\EFilters\Blocks\Blocks;
use Savks\ESearch\Builder\Result as ESearchResult;

/**
 * @mixin ESearchResult
 */
class Result
{
    /**
     * @var ESearchResult
     */
    protected ESearchResult $result;

    /**
     * @var Blocks
     */
    public readonly Blocks $blocks;

    /**
     * @param ESearchResult $result
     * @param Blocks $blocks
     */
    public function __construct(ESearchResult $result, Blocks $blocks)
    {
        $this->result = $result;
        $this->blocks = $blocks;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->{$name};
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->{$name}(...$arguments);
    }
}
