<?php

declare(strict_types=1);

namespace Payever\Payments\Twig;

class WidgetLogic implements WidgetLogicInterface
{
    /**
     * Determines whether the express widget should be displayed on the cart page.
     *
     * @return bool True if the express widget should be displayed, false otherwise.
     */
    public function shouldShowExpressWidgetOnCart(): bool
    {
        return (bool) \PayeverConfig::shouldShowExpressWidgetOnCart();
    }

    /**
     * Determines whether the express widget should be displayed on the product page.
     *
     * @return bool True if the express widget should be shown, false otherwise.
     */
    public function shouldShowExpressWidgetOnProduct(): bool
    {
        return (bool) \PayeverConfig::shouldShowExpressWidgetOnProduct();
    }

    /**
     * Renders the HTML output of the ExpressWidget for a specified article number.
     *
     * @param string|null $articleNum The article number to be used by the widget. Defaults to null if no value is provided.
     *
     * @return string The HTML content of the rendered ExpressWidget.
     */
    public function renderShowExpressWidget($articleNum = null): string
    {
        $expressWidget = new \ExpressWidget(['articleNumber' => $articleNum]);
        return $expressWidget->getWidgetHtml();
    }
}
