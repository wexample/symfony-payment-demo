<?php

namespace Wexample\SymfonyPaymentDemo\Traits;

use Wexample\SymfonyPaymentDemo\WexampleSymfonyPaymentDemoBundle;
use Wexample\SymfonyHelpers\Traits\BundleClassTrait;

trait SymfonyPaymentDemoBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyPaymentDemoBundle::class;
    }
}
