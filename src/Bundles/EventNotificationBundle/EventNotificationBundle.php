<?php

namespace App\Bundles\EventNotificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventNotificationBundle extends Bundle
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
