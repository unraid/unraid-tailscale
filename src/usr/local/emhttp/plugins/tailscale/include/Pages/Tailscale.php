<?php

/*
    Copyright (C) 2025  Derek Kaser

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace Tailscale;

use EDACerton\PluginUtils\Translator;

$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
require_once "{$docroot}/plugins/tailscale/include/common.php";

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(PLUGIN_ROOT);

$tailscaleConfig = $tailscaleConfig ?? new Config();

if ( ! $tailscaleConfig->Enable) {
    echo($tr->tr("tailscale_disabled"));
    return;
}

$tailscaleInfo = $tailscaleInfo ?? new Info($tr);
?>
<link type="text/css" rel="stylesheet" href="/plugins/tailscale/style.css">
<script src="/webGui/javascript/jquery.tablesorter.widgets.js"></script>

<script src="/plugins/tailscale/lib/select2/select2.min.js"></script>
<link href="/plugins/tailscale/lib/select2/select2.min.css" rel="stylesheet" />

<script src="/plugins/tailscale/lib/ipaddr.min.js"></script>

<style>
.select2-container{
    margin: 10px 12px 10px 0;
}
</style>

<script>

function tailscaleControlsDisabled(val) {
    $('#configTable_refresh').prop('disabled', val);
}
function showTailscaleConfig() {
  tailscaleControlsDisabled(true);
  $.post('/plugins/tailscale/include/data/Config.php',{action: 'get'},function(data){
    clearTimeout(timers.refresh);
    $("#configTable").trigger("destroy");
    $('#configTable').html(data.config);
    $("#routesTable").trigger("destroy");
    $('#routesTable').html(data.routes);
    $("#connectionTable").trigger("destroy");
    $('#connectionTable').html(data.connection);
    $('div.spinner.fixed').hide('fast');
    $("#exitNodeSelect").select2();
    if ($("#funnelPortSelect").length) {
        $("#funnelPortSelect").select2();
    }
    tailscaleControlsDisabled(false);
    validateTailscaleRoute();
  },"json");
}
async function setFeature(feature, enable) {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'set-feature', feature: feature, enable: enable});
    showTailscaleConfig();
}
async function setAutoUpdate(enable) {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'set-auto-update', enable: enable});
    showTailscaleConfig();
}
async function setAdvertiseExitNode(enable) {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'set-advertise-exit-node', enable: enable});
    showTailscaleConfig();
}
async function tailscaleUp() {
  $('div.spinner.fixed').show('fast');
  tailscaleControlsDisabled(true);
  var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'up'});
  $('#tailscaleUpLink').attr('href', res);
  $('#tailscaleUpLink').text(res);
  window.open(res);
  $('div.spinner.fixed').hide('fast');
  tailscaleControlsDisabled(false);
}
async function setTailscaleExitNode() {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'exit-node', node: $('#exitNodeSelect').val()});
    showTailscaleConfig();
}
async function setFunnelPort() {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'funnel-port', port: $('#funnelPortSelect').val()});
    showTailscaleConfig();
}
async function removeTailscaleRoute(route) {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'remove-route', route: route});
    showTailscaleConfig();
}
async function addTailscaleRoute() {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'add-route', route: $('#tailscaleRoute').val()});
    showTailscaleConfig();
}
async function setTailscaleRelayPort() {
    $('div.spinner.fixed').show('fast');
    tailscaleControlsDisabled(true);
    var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'set-relay-port', port: $('#tailscaleRelayPort').val()});
    showTailscaleConfig();
}

const CIDRResult = Object.freeze({
    VALID: 'valid',
    INVALID: 'invalid',
    EMPTY: 'empty',
    HOSTBITS_SET: 'hostbits_set'
});

function isValidCIDR(ip) {
    if (ip === undefined || ip.trim() === '') {
        return CIDRResult.EMPTY;
    }

    try {
        // Parse and validate CIDR notation
        const [addr, prefix] = ipaddr.parseCIDR(ip);
        
        // Get the network address with host bits cleared
        let networkAddr;
        if (addr.kind() === 'ipv4') {
            networkAddr = ipaddr.IPv4.networkAddressFromCIDR(ip);
        } else {
            networkAddr = ipaddr.IPv6.networkAddressFromCIDR(ip);
        }
        
        // Check if the original address matches the network address
        if (addr.toString() !== networkAddr.toString()) {
            return CIDRResult.HOSTBITS_SET;
        }

        return CIDRResult.VALID;
    } catch (e) {
        return CIDRResult.INVALID;
    }
}

function validateTailscaleRoute() {
    switch(isValidCIDR($('#tailscaleRoute').val())) {
        case CIDRResult.VALID:
            $('#tailscaleRouteValidation').text('');
            $('#addTailscaleRoute').prop('disabled', false);
            break;
        case CIDRResult.HOSTBITS_SET:
            $('#tailscaleRouteValidation').text('Invalid CIDR: Host bits may not be set').css('color', 'red');
            $('#addTailscaleRoute').prop('disabled', true);
            break;
        case CIDRResult.EMPTY:
            $('#tailscaleRouteValidation').text('');
            $('#addTailscaleRoute').prop('disabled', true);
            break;
        case CIDRResult.INVALID:
        default:
            $('#tailscaleRouteValidation').text('Invalid CIDR').css('color', 'red');
            $('#addTailscaleRoute').prop('disabled', true);
            break;
    }
}

showTailscaleConfig();
</script>

<!-- TODO: Get these warnings with the table -->
<?= Utils::formatWarning($tailscaleInfo->getTailscaleLockWarning()); ?>
<?= Utils::formatWarning($tailscaleInfo->getTailscaleNetbiosWarning()); ?>
<?= Utils::formatWarning($tailscaleInfo->getKeyExpirationWarning()); ?>

<table id='connectionTable' class="unraid statusTable tablesorter"><tr><td><div class="spinner"></div></td></tr></table><br>
<table id='configTable' class="unraid statusTable tablesorter"><tr><td><div class="spinner"></div></td></tr></table><br>
<table id='routesTable' class="unraid statusTable tablesorter"><tr><td><div class="spinner"></div></td></tr></table><br>
<table>
    <tr>
        <td style="vertical-align: top">
          <input type="button" id="configTable_refresh" value="Refresh" onclick="showTailscaleConfig()">
        </td>
    </tr>
</table>