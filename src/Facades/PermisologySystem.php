<?php

namespace PermisologySystem\PermisologySystem\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \PermisologySystem\PermisologySystem\PermisologySystem
 */
class PermisologySystem extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \PermisologySystem\PermisologySystem\PermisologySystem::class;
    }
}
