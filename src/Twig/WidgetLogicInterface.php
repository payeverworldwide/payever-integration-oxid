<?php

declare(strict_types=1);

namespace Payever\Payments\Twig;

interface WidgetLogicInterface
{
    public function shouldShowExpressWidgetOnCart(): bool;

    public function shouldShowExpressWidgetOnProduct(): bool;

    public function renderShowExpressWidget($articleNum = null): string;
}
