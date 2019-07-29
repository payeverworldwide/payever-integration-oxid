[{$smarty.block.parent}]
[{assign var='displayBasketId' value=$oViewConf->displayBasketId()}]
[{if $displayBasketId eq 1}]
    <td valign="top" class="listfilter" height="20">
        <div class="r1"><div class="b1">
                <input class="listedit" type="text" size="30" maxlength="128" name="where[oxorder][basketid]" value="[{ $where.oxorder.basketid }]">
            </div>
        </div>
    </td>
[{/if}]