[{include file="headitem.tpl" title="Payever Configuration"}]
[{oxscript include="js/libs/jquery.min.js"}]
[{assign var="sOxidBasePath" value=$oViewConf->getSslSelfLink()}]
[{assign var="sPayeverCssPath" value=$oViewConf->getModuleUrl('payever', 'out/admin/src/css/payever_admin.css')}]
<link rel="stylesheet" href="[{$sPayeverCssPath}]" type="text/css" />
<div class="payever-config-header">
    <div class="payever-config-logo">
        <a href="https://payever.de/" title="payever.de" target="_new">
            <img src="[{$oViewConf->getModuleUrl('payever')}][{oxmultilang ident='PAYEVER_LOGO'}]" alt="Payever" border="0" >
        </a>
    </div>
    <div class="payever-config-versions">
        [{if $payever_version_info}]
            <b>OXID:</b> v[{$payever_version_info.oxid}] <br>
            <b>Payever:</b> v[{$payever_version_info.payever}]
            [{if $payever_new_version}]
                <span style="color:red;"> - [{ oxmultilang ident="PAYEVER_VERSION_MESSAGE" }]
                    [{if $payever_new_version.filename}]
                        [{ oxmultilang ident="PAYEVER_VERSION_DOWNLOAD" }] <a href="[{$payever_new_version.filename}]">v.[{$payever_new_version.version}]</a> [{ oxmultilang ident="PAYEVER_VERSION_AND_UPDATE" }]
                    [{/if}]
                </span>
            [{/if}]<br>
            <b>PHP:</b> v[{$payever_version_info.php}] <br>
        [{/if}]
    </div>
    <div class="payever-embedded-support">
        <b><a href="javascript:void(0);" onclick="return pe_chat_btn(event);">[{ oxmultilang ident="PAYEVER_CHAT_TITLE" }]</a></b>
        <p>[{ oxmultilang ident="PAYEVER_CHAT_DESCRIPTION" }]</p>
    </div>
</div>
<div class="payever-message-container">
    [{if $payever_error_message}]
        <p style="color:red;">[{$payever_error_message}]</p>
    [{/if}]
    [{if $payever_flash_messages}]
        [{foreach from=$payever_flash_messages item=flashMessage}]
            <p style="color:green;">[{$flashMessage}]</p><br/>
        [{/foreach}]
    [{/if}]
    [{if $payever_error eq 4}]
        <p style="color:red;">[{ oxmultilang ident="PAYEVER_ADMIN_ERROR_SYNC" }]</p>
    [{elseif $payever_error eq 2}]
        <p style="color:green;">[{ oxmultilang ident="PAYEVER_ADMIN_SUCCESS" }]</p>
    [{elseif $payever_error eq 3}]
        <p style="color:green;">[{ oxmultilang ident="PAYEVER_ADMIN_SUCCESS_SYNC" }]</p>
    [{elseif $payever_error eq 5}]
        <p style="color:green;">[{ oxmultilang ident="PAYEVER_ADMIN_SUCCESS_SET_LIVE" }]</p>
    [{elseif $payever_error eq 6}]
        <p style="color:green;">[{ oxmultilang ident="PAYEVER_ADMIN_SUCCESS_SET_SANDBOX" }]</p>
    [{/if}]
</div>
<hr/>
<div id="payever_admin" style="display:none">
<label class="pe_map_header">[{ oxmultilang ident="PAYEVER_ADMIN" }]</label>
<div id="payever_admin_iframe"></div>
</div>
<div style="padding:20px;" id ="payever_config">
    <form name="myedit" id="myedit" action="[{$oViewConf->getSelfLink()}]" method="post">
        <input type="hidden" name="cl" value="[{$oViewConf->getActiveClassName()}]">
       [{$oViewConf->getHiddenSid()}]

        <div class="payeverCont" >
            <div class="cntRow">
                <div class="pe_payment_title">
                    <div class="cntRgt" id="config_0">
                        <div class="payever-config-section">
                            <div class="payever-config-section-title">[{ oxmultilang ident="PAYEVER_API_CONFIGURATION" }]</div>

                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[testMode]" value="0" />
                                    [{ oxmultilang ident="PAYEVER_TEST_MODE" }] &nbsp;
                                    <select class="editinput" name="payever_config[testMode]">
                                        <option value="0" [{if $payever_config.testMode == 0}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_SANDBOX_MODE" }]</option>
                                        <option value="1" [{if $payever_config.testMode == 1}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_LIVE_MODE" }]</option>
                                    </select>
                                <dd><span>[{ oxmultilang ident="PAYEVER_TEST_MODE_DESCRIPTION" }]</span></dd>
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd>
                                    <label>[{ oxmultilang ident="PAYEVER_CLIENTID" }]</label>
                                </dd>
                                <dt>
                                <input type="text" class="editinput" name="payever_config[clientId]" value="[{$payever_config.clientId}]"/>
                                <dd><span>[{ oxmultilang ident="PAYEVER_CLIENTID_DESCRIPTION" }]</span></dd>
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd>[{ oxmultilang ident="PAYEVER_CLIENT_SECRECT" }]</dd>
                                <dt>
                                <input type="text" class="editinput" name="payever_config[clientSecrect]" value="[{$payever_config.clientSecrect}]"/>
                                <dd><span>[{ oxmultilang ident="PAYEVER_CLIENT_SECRECT_DESCRIPTION" }]</span></dd>
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd>[{ oxmultilang ident="PAYEVER_SLUG" }]</dd>
                                <dt>
                                    <input type="text" class="editinput" name="payever_config[slug]" value="[{$payever_config.slug}]"/>
                                    <dd><span>[{ oxmultilang ident="PAYEVER_SLUG_DESCRIPTION" }]</span></dd>
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                        </div>

                        <div class="payever-config-section">
                            <div class="payever-config-section-title">[{ oxmultilang ident="PAYEVER_API_APPEARANCE" }]</div>

                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[defaultLanguage]" value="" />
                                    [{ oxmultilang ident="PAYEVER_DEFAULT_LANGUAGE" }] &nbsp;
                                    <select class="editinput" name="payever_config[defaultLanguage]">
                                        <option value="" [{if $payever_config.defaultLanguage == ''}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_DEFAULT_LANGUAGE_TEXT" }]</option>
                                        <option value="en" [{if $payever_config.defaultLanguage == 'en'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_EN_TEXT" }]</option>
                                        <option value="de" [{if $payever_config.defaultLanguage == 'de'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_DE_TEXT" }]</option>
                                        <option value="es" [{if $payever_config.defaultLanguage == 'es'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_ES_TEXT" }]</option>
                                        <option value="no" [{if $payever_config.defaultLanguage == 'no'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_NO_TEXT" }]</option>
                                        <option value="da" [{if $payever_config.defaultLanguage == 'da'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_DA_TEXT" }]</option>
                                        <option value="sv" [{if $payever_config.defaultLanguage == 'sv'}]selected="selected"[{/if}]>[{ oxmultilang ident="PAYEVER_SV_TEXT" }]</option>
                                    </select>
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                <input type="hidden" name="payever_config[displayPaymentIcon]" value="0" />
                                <input type="checkbox" class="editinput" name="payever_config[displayPaymentIcon]" value="1"
                                    [{if $payever_config.displayPaymentIcon}]checked="checked"[{/if}] />&nbsp;
                                [{ oxmultilang ident="PAYEVER_DISPLAY_ICON" }]<br />
                            </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                <input type="hidden" name="payever_config[displayPaymentDescription]" value="0" />
                                <input type="checkbox" class="editinput" name="payever_config[displayPaymentDescription]" value="1"
                                 [{if $payever_config.displayPaymentDescription}]checked="checked"[{/if}] />&nbsp;
                                 [{ oxmultilang ident="PAYEVER_DISPLAY_DESCRIPTION" }]
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[redirectToPayever]" value="0" />
                                    <input type="checkbox" class="editinput" name="payever_config[redirectToPayever]" value="1"
                                    [{if $payever_config.redirectToPayever}]checked="checked"[{/if}] />&nbsp;
                                    [{ oxmultilang ident="PAYEVER_IS_REDIRECT" }]
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[displayBasketId]" value="0" />
                                    <input type="checkbox" class="editinput" name="payever_config[displayBasketId]" value="1"
                                    [{if $payever_config.displayBasketId}]checked="checked"[{/if}] />&nbsp;
                                    [{ oxmultilang ident="PAYEVER_DISPLAY_BASKET_ID" }]
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                        </div>

                        <div class="payever-config-section">
                            <div class="payever-config-section-title">[{ oxmultilang ident="PAYEVER_API_PRODUCT_AND_INVENTORY" }]</div>

                            <dl>
                                <dt>
                                    <input type="hidden" name="payever_config[payeverProductsSyncExternalId]"
                                           value="[{$payever_config.payeverProductsSyncExternalId}]" />
                                </dt>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[payeverProductsSyncEnabled]" value="0" />
                                    <input type="checkbox" class="editinput" name="payever_config[payeverProductsSyncEnabled]" value="1"
                                           [{if $payever_config.payeverProductsSyncEnabled}]checked="checked"[{/if}] />&nbsp;
                                    [{ oxmultilang ident="PAYEVER_PRODUCTS_SYNC_ENABLED" }]<br />
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    <input type="hidden" name="payever_config[payeverProductsOutwardSyncEnabled]" value="0" />
                                    <input type="checkbox" class="editinput" name="payever_config[payeverProductsOutwardSyncEnabled]" value="1"
                                           [{if $payever_config.payeverProductsOutwardSyncEnabled}]checked="checked"[{/if}] />&nbsp;
                                    [{ oxmultilang ident="PAYEVER_PRODUCTS_OUTWARD_SYNC_ENABLED" }] &nbsp;
                                </dt>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    [{ oxmultilang ident="PAYEVER_PRODUCTS_SYNC_MODE" }] &nbsp;
                                    <select name="payever_config[payeverProductsSyncMode]">
                                        <option value="instant" [{if $payever_config.payeverProductsSyncMode == "instant"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_PRODUCTS_SYNC_MODE_INSTANT" }]</option>
                                        <option value="cron" [{if $payever_config.payeverProductsSyncMode == "cron"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_PRODUCTS_SYNC_MODE_CRON" }]</option>
                                    </select>
                                </dt>
                            </dl>
                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    [{ oxmultilang ident="PRODUCTS_SYNC_CURRENCY_RATE_SOURCE" }] &nbsp;
                                    <select name="payever_config[payeverProductsCurrencyRateSource]">
                                        <option value="instant" [{if $payever_config.payeverProductsCurrencyRateSource == "oxid"}]selected[{/if}]>[{ oxmultilang ident="PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_OXID" }]</option>
                                        <option value="cron" [{if $payever_config.payeverProductsCurrencyRateSource == "payever"}]selected[{/if}]>[{ oxmultilang ident="PRODUCTS_SYNC_CURRENCY_RATE_SOURCE_PAYEVER" }]</option>
                                    </select>
                                </dt>
                            </dl>
                        </div>

                        <div class="payever-config-section">
                            <div class="payever-config-section-title">[{ oxmultilang ident="PAYEVER_API_LOGGING" }]</div>

                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    [{ oxmultilang ident="PAYEVER_LOG_LEVEL" }] &nbsp;
                                    <select name="payever_config[logLevel]">
                                        <option value="error" [{if $payever_config.logLevel == "error"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_ERRORS" }]</option>
                                        <option value="info" [{if $payever_config.logLevel == "info"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_INFO" }]</option>
                                        <option value="debug" [{if $payever_config.logLevel == "debug"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_DEBUG" }]</option>
                                    </select>
                                    [{if $log_filename}]
                                        <br>
                                        <dd><span>[{ oxmultilang ident="PAYEVER_LOG_FILEPATH" }]: [{$log_filename}]</span></dd>
                                    [{/if}]
                                </dt>
                                <div class="spacer"></div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="fnc" value="synchronize">
    </form>
    <div class="cntExLft">
        <input type="submit" class="payever-config-btn" name="save" value="[{ oxmultilang ident="GENERAL_SAVE" }]" onclick="document.myedit.fnc.value='save'; document.myedit.submit();" style="margin:6em 3em 0;"/>
    </div>
    <div class="cntExLft">
        <input type="submit" class="payever-config-btn" name="synchronize" value="[{ oxmultilang ident="PAYEVER_SYNCHRONIZE" }]" onclick="document.myedit.fnc.value='synchronize'; synchronize();" />
    </div>
    [{if $isset_live eq 1}]
        <div class="cntExLft">
            <input type="submit" class="payever-config-btn" name="setApiKeys" value="[{ oxmultilang ident="PAYEVER_SET_LIVE" }]" onclick="document.myedit.fnc.value='setLive'; setLive();" />
        </div>
    [{else}]
        <div class="cntExLft">
            <input type="submit" class="payever-config-btn" name="setApiKeys" value="[{ oxmultilang ident="PAYEVER_SET_SANDBOX" }]" onclick="document.myedit.fnc.value='setSandbox'; setSandbox();" />
        </div>
    [{/if}]
    <div class="cntExLft">
        <input type="submit" class="payever-config-btn" name="exportProductsAndInventory"
               value="[{ oxmultilang ident="PAYEVER_PRODUCTS_AND_INVENTORY_EXPORT" }]"
        [{if not $payever_config.payeverProductsSyncEnabled or not $payever_config.payeverProductsOutwardSyncEnabled}]
                disabled="disabled"
        [{/if}]
        onclick="document.myedit.fnc.value='exportProductsAndInventory'; payeverDoExport('[{$payever_config.payeverProductsSyncExternalId}]', null, this.data_page === undefined ? 0 : this.data_page, this.aggregate === undefined ? 0 : this.aggregate);" />
    </div>
    [{if $log_file_exists eq 1}]
        <div class="cntExLft">
            <input type="submit" class="payever-config-btn" name="downloadLogFile" value="[{ oxmultilang ident="PAYEVER_DOWNLOAD_LOG" }]" onclick="document.myedit.fnc.value='downloadLogFile'; downloadLogFile();" />
        </div>
    [{/if}]
    <div class="cntExLft">
        <input type="submit" class="payever-config-btn" name="downloadAppLogFile" value="Download App Log" onclick="document.myedit.fnc.value='downloadAppLogFile'; downloadAppLogFile();" />
    </div>
    <div class="cntExLft">
        <input type="submit" class="payever-config-btn" name="clearCache" value="Clear Cache" onclick="document.myedit.fnc.value='clearCache'; document.myedit.submit();" />
    </div>
</div>
<script type="text/javascript">
    function synchronize() {
        if (confirm([{ oxmultilang ident="PAYEVER_SYNCHRONIZE_CONFIRM"}])) {
            document.myedit.submit();
        }
    }
    [{if $isset_live eq 1}]
        function setLive() {
            if (confirm([{ oxmultilang ident="PAYEVER_SET_LIVE_CONFIRM"}])) {
                document.myedit.submit();
            }
        }
    [{else}]
        function setSandbox() {
            if (confirm([{ oxmultilang ident="PAYEVER_SET_SANDBOX_CONFIRM"}])) {
                document.myedit.submit();
            }
        }
    [{/if}]
    function exportProductsAndInventory() {
        if (confirm([{ oxmultilang ident="PAYEVER_SET_SANDBOX_CONFIRM"}])) {
            document.myedit.submit();
        }
    }
    function payeverDoExport(externalId, exportUrl, page, aggregate) {
        if (!exportUrl) {
            exportUrl = '[{$sOxidBasePath}]' + '/admin/index.php?cl=payeverproductsexport&fnc=export'
        }
        let messageContainerSelector = '.payever-message-container';
        $(messageContainerSelector).empty();
        $.ajax({
            type: 'POST',
            url: exportUrl + (page ? '&page=' + page + (aggregate ? '&aggregate=' + aggregate : '') : ''),
            data : {
                exportProductsAndInventories: true,
                externalId: externalId,
                cl: 'payeverproductsexport',
                fnc: 'export',
                stoken: $('#myedit input[name="stoken"]').val()
            },
            success: function (data) {
                if (data.error && data.error.length > 0) {
                    $(messageContainerSelector).html('<div class="alert alert-danger">' + data.error + '</div>');
                } else {
                    if (data.next_page) {
                        $(messageContainerSelector).html('<div class="alert alert-success">' + 'Processed items: ' + data.aggregate + '</div>');

                        return payeverDoExport(externalId, exportUrl, data.next_page, data.aggregate);
                    } else {
                        $(messageContainerSelector).html('<div class="alert alert-success">' + 'Total processed items: ' + data.aggregate + '</div>');
                    }
                }
            },
            error: function() {
                $(messageContainerSelector).html('<div class="alert alert-danger">Unknown error</div>');
            }
        });
    }
    function downloadLogFile() {
        document.myedit.submit();
    }
    function downloadAppLogFile() {
        document.myedit.submit();
    }
    function clearCache() {
        document.myedit.submit();
    }
    function pe_chat_btn(e) {
        window.zESettings = { analytics: false };

        var s = document.createElement('script');
        s.src = 'https://static.zdassets.com/ekr/snippet.js?key=775ae07f-08ee-400e-b421-c190d7836142';
        s.id = 'ze-snippet';
        s.onload = function () {
            window['zE'] && window['zE']('webWidget', 'open');
            window['zE'] && window['zE']('webWidget:on', 'open', function() { e.target.innerText = '[{ oxmultilang ident="PAYEVER_CHAT_TITLE" }]'; });
        };
        document.head.appendChild(s);

        e.target.innerText = '[{ oxmultilang ident="PAYEVER_LOADING_CHAT" }]';
        e.preventDefault();

        return false;
    }
</script>
[{include file="bottomitem.tpl"}]
