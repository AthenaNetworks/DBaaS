<?php

namespace Tests\Traits;

trait DisablesMiddleware
{
    /**
     * Disable middleware for testing
     *
     * @return void
     */
    protected function disableMiddleware()
    {
        $this->withoutMiddleware();
    }
}
