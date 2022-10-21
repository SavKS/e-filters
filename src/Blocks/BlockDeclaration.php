<?php

namespace Savks\EFilters\Blocks;

abstract class BlockDeclaration
{
    public readonly ?array $aggregations;

    public function __construct()
    {
        $this->aggregations = $this->requiredAggregations();
    }

    abstract protected function requiredAggregations(): ?array;

    /**
     * @return Block[]
     */
    abstract public function toBlocks(array $aggregated): array;
}
