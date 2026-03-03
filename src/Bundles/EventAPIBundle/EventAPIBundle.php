<?php

namespace App\Bundles\EventAPIBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventAPIBundle extends Bundle
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
