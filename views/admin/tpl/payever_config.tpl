[{include file="headitem.tpl" title="Payever Configuration"}]
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
            <b>Payever:</b> v[{$payever_version_info.payever}] <br>
            <b>PHP:</b> v[{$payever_version_info.php}] <br>
        [{/if}]
    </div>
</div>
[{if $payever_error_message}]
    <p style="color:red;">[{$payever_error_message}]</p>
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
                            <div class="payever-config-section-title">[{ oxmultilang ident="PAYEVER_API_LOGGING" }]</div>

                            <dl>
                                <dd class="cntExLft"></dd>
                                <dt>
                                    [{ oxmultilang ident="PAYEVER_DEBUG_MODE" }] &nbsp;
                                    <select name="payever_config[debugMode]">
                                        <option value="error" [{if $payever_config.debugMode == "error"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_ERRORS" }]</option>
                                        <option value="info" [{if $payever_config.debugMode == "info"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_INFO" }]</option>
                                        <option value="debug" [{if $payever_config.debugMode == "debug"}]selected[{/if}]>[{ oxmultilang ident="PAYEVER_LOG_DEBUG" }]</option>
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
    [{if $log_file_exists eq 1}]
        <div class="cntExLft">
            <input type="submit" class="payever-config-btn" name="downloadLogFile" value="[{ oxmultilang ident="PAYEVER_DOWNLOAD_LOG" }]" onclick="document.myedit.fnc.value='downlaodLogFile'; downlaodLogFile();" />
        </div>
    [{/if}]
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
    function downlaodLogFile() {
        document.myedit.submit();
    }
</script>
[{include file="bottomitem.tpl"}]
