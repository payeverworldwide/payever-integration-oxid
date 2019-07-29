[{$smarty.block.parent}]
[{assign var='displayBasketId' value=$oViewConf->displayBasketId()}]
[{if $listitem->oxorder__oxstorno->value == 1}]
    [{assign var="listclass" value=listitem3}]
    [{else}]
    [{if $listitem->blacklist == 1}]
    [{assign var="listclass" value=listitem3}]
    [{else}]
    [{assign var="listclass" value=listitem$blWhite}]
    [{/if}]
    [{/if}]
[{if $listitem->getId() == $oxid}]
    [{assign var="listclass" value=listitem4}]
[{/if}]
[{if $displayBasketId eq 1}]
    <td valign="top" class="[{ $listclass}]" height="15">
        <div class="listitemfloating">
            <a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxorder__oxid->value}]');"
               class="[{ $listclass}]">[{ $listitem->oxorder__basketid->value }]</a>
        </div>
    </td>
[{/if}]