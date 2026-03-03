<?php

namespace App\Bundles\EventCoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventCoreBundle extends Bundle
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
