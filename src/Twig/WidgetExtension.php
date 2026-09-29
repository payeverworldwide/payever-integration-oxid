<?php

declare(strict_types=1);

namespace Payever\Payments\Twig;

use Psr\Container\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

// Disable Twig extension for OXID 6
if (\PayeverConfig::getOxidMajorVersion() < 7) {
    class WidgetExtension {
        public function __construct(
            ContainerInterface $container
        ) {
            //
        }
    }

    return;
}

class WidgetExtension extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('shouldShowExpressWidgetOnCart', [
                $this->container->get(WidgetLogicInterface::class),
                'shouldShowExpressWidgetOnCart'
            ]),
            new TwigFunction('shouldShowExpressWidgetOnProduct', [
                $this->container->get(WidgetLogicInterface::class),
                'shouldShowExpressWidgetOnProduct'
            ]),
            new TwigFunction('renderShowExpressWidget', [
                $this->container->get(WidgetLogicInterface::class),
                'renderShowExpressWidget'
            ]),
        ];
    }
}
