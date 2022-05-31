<?php

namespace Savks\EFilters\Blocks;

abstract class BlockDeclaration
{
    /**
     * @var array|null
     */
    public readonly ?array $aggregations;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->aggregations = $this->requiredAggregations();
    }

    /**
     * @return array|null
     */
    abstract protected function requiredAggregations(): ?array;

    /**
     * @return Block[]
     */
    abstract public function toBlocks(array $aggregated): array;
}
