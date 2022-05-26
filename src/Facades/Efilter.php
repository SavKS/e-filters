<?php

namespace Savks\EFilters\Facades;

use Illuminate\Support\Facades\Facade;

class Efilter extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'efilter';
    }
}
