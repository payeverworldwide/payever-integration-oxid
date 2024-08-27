[{$smarty.block.parent}]
[{assign var="articleNum" value=$oDetailsProduct->oxarticles__oxartnum->value}]
[{php}]
    $articleNum = $this->get_template_vars('articleNum');
    $expressWidget = new ExpressWidget(['articleNumber' => $articleNum]);
    echo $expressWidget->getWidgetHtml();
[{/php}]
