[{$smarty.block.parent}]
[{assign var='displayBasketId' value=$oViewConf->displayBasketId()}]
[{if $displayBasketId eq 1}]
    <td class="listheader" height="15">
        <a href="Javascript:top.oxid.admin.setSorting( document.search, 'oxorder', 'basketid', 'asc');document.search.submit();"
           class="listheader">[{oxmultilang ident="PAYEVER_ORDER_BASKETID"}]
        </a>
    </td>
[{/if}]