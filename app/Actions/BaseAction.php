<?php

namespace App\Actions;

abstract class BaseAction
{
    /**
     * Execute the action.
     */
    abstract public function execute(mixed ...$args): mixed;
}
