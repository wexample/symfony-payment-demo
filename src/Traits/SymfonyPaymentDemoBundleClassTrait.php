<?php

namespace Wexample\SymfonyPaymentDemo\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyPaymentDemo\WexampleSymfonyPaymentDemoBundle;

trait SymfonyPaymentDemoBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyPaymentDemoBundle::class;
    }
}
