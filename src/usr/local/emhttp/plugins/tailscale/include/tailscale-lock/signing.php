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

if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
    throw new \RuntimeException("Common file not loaded.");
}

$tr = $tr ?? new Translator(PLUGIN_ROOT);
?>
<h3><?= $tr->tr("tailscale_lock"); ?></h3>
<h4><?= $tr->tr("lock.sign"); ?></h4>
<p>
    <?= $tr->tr("lock.signing_node"); ?>
</p>
<p>
<?= $tr->tr("lock.signing_instructions"); ?>
</p>
<?php
    if ( ! isset($tailscaleInfo)) {
        echo("Tailscale info not defined");
        return;
    }
?>

<script>
function controlsDisabled(val) {
    $('#lockTable_signnode').prop('disabled', val);
}
function loadFilteredPeers() {
  controlsDisabled(true);
  $.post('/plugins/tailscale/include/data/Lock.php',{action: 'get',mullvad: $("#lockTable_mullvad").prop('checked')},function(data){
    clearTimeout(timers.refresh);
    $("#lockTable").trigger("destroy");
    $('#lockTable').html(data.html);
    $('#lockTable').tablesorter({
      widthFixed : true,
      sortList: [[0,0]],
      sortAppend: [[0,0]],
      widgets: ['stickyHeaders','filter','zebra'],
      widgetOptions: {
        // on black and white, offset is height of #menu
        // on azure and gray, offset is height of #header
        stickyHeaders_offset: ($('#menu').height() < 50) ? $('#menu').height() : $('#header').height(),
        filter_columnFilters: true,
        zebra: ["normal-row","alt-row"]
      }
    });
    controlsDisabled(false);
  },"json");
}
loadFilteredPeers();
</script>

<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/tailscale/approve-nodes.php">
<table id='lockTable' class="unraid lockTable tablesorter"><tr><td>&nbsp;</td></tr></table><br>

<input type="submit" id="lockTable_signnode" name="#apply" value="<?= $tr->tr('Sign'); ?>">
<input type="checkbox" id="lockTable_mullvad" onChange="loadFilteredPeers()">Display unsigned Mullvad nodes
</form>
