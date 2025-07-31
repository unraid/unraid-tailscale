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

$tailscaleConfig = $tailscaleConfig ?? new Config();

if (( ! isset($var)) || ( ! isset($display))) {
    echo("Missing required WebGUI variables");
    return;
}

// Used to disable buttons that should not be used over Tailscale since the connection will break.
// (erase config, reauth, etc.)
$tailscaleDisconnect = " disabled";

if ($tailscaleConfig->Enable) {
    $tailscaleInfo = $tailscaleInfo ?? new Info($tr);
    if ( ! $tailscaleInfo->connectedViaTS()) {
        $tailscaleDisconnect = "";
    }
}

?>

<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.filetree.css');?>">
<link type="text/css" rel="stylesheet" href="<?= Utils::auto_v('/webGui/styles/jquery.switchbutton.css');?>">
<span class="status vhshift"><input type="checkbox" class="advancedview"></span>
<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#file"
    value="tailscale/tailscale.cfg">
<input type="hidden" name="#cleanup" value="">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/tailscale/restart.sh">

<h3><?= $tr->tr("settings.system_settings"); ?></h3>

<div class="advanced">
    <dl>
        <dt><?= $tr->tr("settings.enable_tailscale"); ?></dt>
        <dd>
            <select name='ENABLE_TAILSCALE' size='1' class='narrow'>
                <?= Utils::make_option($tailscaleConfig->Enable, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $tailscaleConfig->Enable, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>

    <dl>
        <dt><?= $tr->tr("settings.unraid_listen"); ?></dt>
        <dd>
            <select name='INCLUDE_INTERFACE' size='1' class='narrow'>
                <?= Utils::make_option($tailscaleConfig->IncludeInterface, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $tailscaleConfig->IncludeInterface, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.unraid_listen"); ?></blockquote>

    <dl>
        <dt><?= $tr->tr("settings.ip_forward"); ?></dt>
        <dd>
            <select name='SYSCTL_IP_FORWARD' size='1' class='narrow'>
                <?= Utils::make_option($tailscaleConfig->IPForward, '1', $tr->tr("yes"));?>
                <?= Utils::make_option( ! $tailscaleConfig->IPForward, '0', $tr->tr("no"));?>
            </select>
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.ip_forward"); ?></blockquote>
</div>

<dl>
    <dt><?= $tr->tr("settings.taildrop"); ?></dt>
    <dd>
        <input type="text" id="taildropdir" name="TAILDROP_DIR" autocomplete="off" spellcheck="false" class="narrow"
        data-pickfolders="true" data-pickfilter="HIDE_FILES_FILTER" data-pickroot="/mnt" pattern="^[^\\]*/$"
        value="<?= htmlspecialchars($tailscaleConfig->TaildropDir);?>">
    </dd>
</dl>
<blockquote class='inline_help'><?= $tr->tr("settings.context.taildrop"); ?></blockquote>

<div class="advanced">
    <h3><?= $tr->tr("settings.services"); ?></h3>

    <dl>
        <dt><?= $tr->tr("settings.wireguard"); ?></dt>
        <dd>
            <input type="number" name="WG_PORT" class="narrow" min="0" max="65535" value="<?= $tailscaleConfig->WgPort; ?>" placeholder="0">
        </dd>
    </dl>
    <blockquote class='inline_help'><?= $tr->tr("settings.context.wireguard"); ?></blockquote>
</div>

<h3><?= $tr->tr("settings.outbound_network"); ?></h3>

<dl>
    <dt><?= $tr->tr("settings.subnets"); ?></dt>
    <dd>
        <select name='ACCEPT_ROUTES' id='ACCEPT_ROUTES' onchange='showSettingWarning("subnet","#ACCEPT_ROUTES");' size='1' class='narrow'>
            <?= Utils::make_option( ! $tailscaleConfig->AllowRoutes, '0', $tr->tr("no"));?>
            <?= Utils::make_option($tailscaleConfig->AllowRoutes, '1', $tr->tr("yes"));?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>
    <?= $tr->tr("settings.context.subnets"); ?>
</blockquote>

<dl>
    <dt><?= $tr->tr("settings.dns"); ?></dt>
    <dd>
        <select name='ACCEPT_DNS' id='ACCEPT_DNS' onchange='showSettingWarning("dns","#ACCEPT_DNS");' size='1' class='narrow'>
            <?= Utils::make_option( ! $tailscaleConfig->AllowDNS, '0', $tr->tr("no"));?>
            <?= Utils::make_option($tailscaleConfig->AllowDNS, '1', $tr->tr("yes"));?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>
    <?= $tr->tr("settings.context.dns"); ?>
</blockquote>

<?php if (Utils::isFunnelAllowed()) { ?>

<dl>
    <dt><?= $tr->tr("settings.funnel"); ?></dt>
    <dd>
        <select name='ALLOW_FUNNEL' id='ALLOW_FUNNEL' onchange="showSettingWarning('funnel','#ALLOW_FUNNEL');" size='1' class='narrow'>
            <?= Utils::make_option( ! $tailscaleConfig->AllowFunnel, '0', $tr->tr("no"));?>
            <?= Utils::make_option($tailscaleConfig->AllowFunnel, '1', $tr->tr("yes"));?>
        </select>
    </dd>
</dl>
<blockquote class='inline_help'>
    <?= $tr->tr("settings.context.funnel"); ?>
</blockquote>

<?php } ?>

<h3><?= $tr->tr("settings.save"); ?></h3>

<dl>
    <dt><strong><?= $tr->tr("settings.context.save"); ?></strong></dt>
    <dd>
        <span><input type="submit" name="#apply" value="<?= $tr->tr('Apply'); ?>"><input type="button" id="DONE" value="<?= $tr->tr('Back'); ?>" onclick="done()"></span>
    </dd>
</dl>
</form>

<h3><?= $tr->tr("settings.restart"); ?></h3>

<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/tailscale/restart.sh">
<dl>
    <dt><?= $tr->tr("settings.context.restart"); ?></dt>
    <dd>
        <span><input type="submit" value="<?= $tr->tr('Restart'); ?>"></span>
    </dd>
</dl>
</form>

<?php if (file_exists('/usr/local/emhttp/plugins/plugin-diagnostics/download.php')) { ?>
<h3><?= $tr->tr("settings.diagnostics"); ?></h3>

<form method="GET" action="/plugins/plugin-diagnostics/download.php" target="_blank">
<input type="hidden" name="plugin" value="tailscale">
<dl>
    <dt><?= $tr->tr("settings.context.diagnostics"); ?></dt>
    <dd>
        <span><input type="submit" value="<?= $tr->tr('Download'); ?> "></span>
    </dd>
</dl>
</form>

<?php } ?>

<div class="advanced">
<h3><?= $tr->tr("settings.reauthenticate"); ?></h3>

<dl>
    <dt><?= $tr->tr("settings.context.reauthenticate"); ?></dt>
    <dd>
        <span><input type="button" value="<?= $tr->tr('settings.reauthenticate'); ?>" onclick="expireTailscaleKeyNow()" <?= $tailscaleDisconnect; ?>></span>
    </dd>
</dl>

<h3><?= $tr->tr("settings.erase"); ?></h3>

<form method="POST" action="/update.php" target="progressFrame">
<input type="hidden" name="#command" value="/usr/local/emhttp/plugins/tailscale/erase.sh">
<dl>
    <dt><?= $tr->tr("settings.context.erase"); ?></dt>
    <dd>
        <span><input type="button" value="<?= $tr->tr('Erase'); ?>" onclick="requestErase(this)" <?= $tailscaleDisconnect; ?>><input id="tailscale_erase_confirm" type="submit" value="<?= $tr->tr('Confirm'); ?>" style="display: none;"></span>
    </dd>
</dl>
</form>
</div>

<script src="<?= Utils::auto_v('/webGui/javascript/jquery.filetree.js');?>"></script>
<script src="<?= Utils::auto_v('/webGui/javascript/jquery.switchbutton.js');?>"></script>
<script>
    function requestErase(e) {
        e.disabled = true;
        var confirmButton = document.getElementById('tailscale_erase_confirm');
        confirmButton.style.display = "inline";
    }

    async function expireTailscaleKeyNow() {
        $('div.spinner.fixed').show('fast');
        var res = await $.post('/plugins/tailscale/include/data/Config.php',{action: 'expire-key'});
        location.reload();
    }
</script>
<script>
    $(function() {
        <?= ($var['fsState'] == 'Started') ? "$('#taildropdir').fileTreeAttach();" : ""; ?>

        if ($.cookie('tailscale_view_mode') == 'advanced') {
            $('.advanced').show();
        } else {
            $('.advanced').hide();
        }

        $('.advancedview').switchButton({
            labels_placement: "left",
            on_label: "<?= $tr->tr("settings.advanced"); ?>",
            off_label: "<?= $tr->tr("settings.basic"); ?>",
            checked: $.cookie('tailscale_view_mode') == 'advanced'
        });
        $('.advancedview').change(function(){
            if($('.advancedview').is(':checked')) {
                $('.advanced').show('slow');
            } else {
                $('.advanced').hide('slow');
            }
            $.cookie('tailscale_view_mode', $('.advancedview').is(':checked') ? 'advanced' : 'basic', {expires:3650});
        });
    });

function showSettingWarning(message, element) {
    // If setting the value to 0, we don't need a warning message.
    if ($(element).val() == '0') {
        return;
    }

    const messages = {
        'funnel': "<?= $tr->tr("warnings.funnel"); ?>",
        'subnet': "<?= $tr->tr("warnings.subnet"); ?>",
        'dns': "<?= $tr->tr("warnings.dns"); ?>"
    };

    const links = {
        'funnel': "https://docs.unraid.net/unraid-os/manual/security/tailscale/",
        'subnet': "",
        'dns': ""
    };

    const moreLink = links[message] || "";

    var dialogText = messages[message];
    dialogText += "<br><br><?= $tr->tr("warnings.caution"); ?>";
    if (moreLink) {
        dialogText += "<br><br><?= $tr->tr("warnings.more_info"); ?>";
        dialogText += "<br><br><a href='" + moreLink + "' target='_blank'>" + moreLink + "</a>";
    }

    swal({
        title: "<?= $tr->tr("warning"); ?>",
        text: dialogText,
        type: "warning",
        confirmButtonText: "<?= $tr->tr("accept"); ?>",
        showCancelButton: true,
        cancelButtonText: "<?= $tr->tr("cancel"); ?>",
        html: true
        },
        function(isConfirmed){
            if (!isConfirmed) {
                // Set the select element back to 0
                $(element).val('0');
            }
        }
    );
}

</script>
