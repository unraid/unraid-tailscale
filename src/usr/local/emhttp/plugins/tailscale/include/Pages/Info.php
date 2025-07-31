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

if ( ! $tailscaleConfig->Enable) {
    echo($tr->tr("tailscale_disabled"));
    return;
}

$tailscaleInfo       = $tailscaleInfo ?? new Info($tr);
$tailscaleStatusInfo = $tailscaleInfo->getStatusInfo();
?>
<table class="unraid t1">
    <thead>
        <tr>
            <td><?= $tr->tr('status'); ?></td>
            <td>&nbsp;</td>
        </tr>
    </thead>
    <tbody>
        <?php
            $lockTranslate = $tr->tr("tailscale_lock");

echo Utils::printRow($tr->tr("info.version"), $tailscaleStatusInfo->TsVersion);
echo Utils::printRow($tr->tr("info.health"), $tailscaleStatusInfo->TsHealth);
echo Utils::printRow($tr->tr("info.login"), $tailscaleStatusInfo->LoggedIn);
echo Utils::printRow($tr->tr("info.netmap"), $tailscaleStatusInfo->InNetMap);
echo Utils::printRow($tr->tr("info.online"), $tailscaleStatusInfo->Online);
echo Utils::printRow($tr->tr("info.key_expire"), $tailscaleStatusInfo->KeyExpiration);
echo Utils::printRow($tr->tr("info.tags"), $tailscaleStatusInfo->Tags);
echo Utils::printRow("{$lockTranslate}: " . $tr->tr("enabled"), $tailscaleStatusInfo->LockEnabled);
echo Utils::printRow($tr->tr("info.connected_via"), $tailscaleInfo->connectedViaTS() ? $tr->tr("yes") : $tr->tr("no"));

if ($tailscaleStatusInfo->LockInfo != null) {
    echo Utils::printRow("{$lockTranslate}: " . $tr->tr("info.lock.signed"), $tailscaleStatusInfo->LockInfo->LockSigned);
    echo Utils::printRow("{$lockTranslate}: " . $tr->tr("info.lock.signing"), $tailscaleStatusInfo->LockInfo->LockSigning);
    echo Utils::printRow("{$lockTranslate}: " . $tr->tr("info.lock.node_key"), $tailscaleStatusInfo->LockInfo->NodeKey);
    echo Utils::printRow("{$lockTranslate}: " . $tr->tr("info.lock.public_key"), $tailscaleStatusInfo->LockInfo->PubKey);
}
?>
    </tbody>
</table>
