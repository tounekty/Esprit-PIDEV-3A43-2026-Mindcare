<?php

namespace App\Bundles\EventUIBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventUIBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }

    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }
}
