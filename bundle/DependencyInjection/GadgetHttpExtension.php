<?php

declare(strict_types=1);

namespace Gadget\Http\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\MicroBundleExtensionTrait;

final class GadgetHttpExtension extends Extension
{
    use MicroBundleExtensionTrait;
}
