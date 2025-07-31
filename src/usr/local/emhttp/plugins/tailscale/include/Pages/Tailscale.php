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

<script src="/webGui/javascript/jquery.tablesorter.widgets.js"></script>

<script src="/plugins/tailscale/vendor/select2/select2.min.js"></script>
<link href="/plugins/tailscale/vendor/select2/select2.min.css" rel="stylesheet" />

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
function isValidCIDR(ip) {
    if (ip === undefined) {
        return false;
    }

    var parts = ip.split('/');
    if (parts.length != 2) {
        return false;
    }

    var mask = parseInt(parts[1]);
    if (isNaN(mask) || mask < 0) {
        return false;
    }

    const ipv4Pattern = /^(\d{1,3}\.){3}\d{1,3}$/;
    const ipv6Pattern = /^(?:(?:[a-fA-F\d]{1,4}:){7}(?:[a-fA-F\d]{1,4}|:)|(?:[a-fA-F\d]{1,4}:){6}(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|:[a-fA-F\d]{1,4}|:)|(?:[a-fA-F\d]{1,4}:){5}(?::(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,2}|:)|(?:[a-fA-F\d]{1,4}:){4}(?:(?::[a-fA-F\d]{1,4}){0,1}:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,3}|:)|(?:[a-fA-F\d]{1,4}:){3}(?:(?::[a-fA-F\d]{1,4}){0,2}:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,4}|:)|(?:[a-fA-F\d]{1,4}:){2}(?:(?::[a-fA-F\d]{1,4}){0,3}:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,5}|:)|(?:[a-fA-F\d]{1,4}:){1}(?:(?::[a-fA-F\d]{1,4}){0,4}:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,6}|:)|(?::(?:(?::[a-fA-F\d]{1,4}){0,5}:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:\\.(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}|(?::[a-fA-F\d]{1,4}){1,7}|:)))(?:%[0-9a-zA-Z]{1,})?$/gm;

    if(ipv4Pattern.test(parts[0])) {
        // IPv4
        if(mask > 32) {
            return false;
        }
    } else if (ipv6Pattern.test(parts[0])) {
        // IPv6
        if(mask > 128) {
            return false;
        }
    } else {
        return false;
    }

    return true;
}

function validateTailscaleRoute() {
    if (! $('#tailscaleRoute').length) {
        return;
    }

    if (isValidCIDR($('#tailscaleRoute').val())) {
        $('#addTailscaleRoute').prop('disabled', false);
    } else {
        $('#addTailscaleRoute').prop('disabled', true);
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