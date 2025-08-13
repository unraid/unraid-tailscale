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

$tailscale_dashboard = "<tr><td>" . $tr->tr("tailscale_disabled") . "</td></tr>";

if ($tailscaleConfig->Enable) {
    $tailscaleInfo     = $tailscaleInfo ?? new Info($tr);
    $tailscaleDashInfo = $tailscaleInfo->getDashboardInfo();

    $tailscale_dashboard = Utils::printDash($tr->tr("info.online"), $tailscaleDashInfo->Online);
    $tailscale_dashboard .= Utils::printDash($tr->tr("info.hostname"), $tailscaleDashInfo->HostName);
    $tailscale_dashboard .= Utils::printDash($tr->tr("info.dns"), $tailscaleDashInfo->DNSName);
    $tailscale_dashboard .= Utils::printDash($tr->tr("info.ip"), implode("<br><span class='w26'>&nbsp;</span>", $tailscaleDashInfo->TailscaleIPs));
}

$isResponsiveWebgui = version_compare(parse_ini_file('/etc/unraid-version')['version'] ?? "", '7.2', '>=');
$cardTitleStart     = $isResponsiveWebgui ? "<h3 class='tile-header-main'>" : "";
$cardTitleEnd       = $isResponsiveWebgui ? "</h3>" : "<br>";

echo <<<EOT
    <tbody title="Tailscale">
    <tr>
        <td>
            <span class='tile-header'>
                <span class='tile-header-left'>
                    <img style="margin-right: 8px; width: 32px; height: 32px" src="/plugins/tailscale/tailscale.png" alt="Tailscale">
                    <div class='section'>
                        {$cardTitleStart} Tailscale {$cardTitleEnd}
                    </div>
                </span>
                <span class='tile-header-right'>
                    <span class='tile-header-right-controls'><a href="/Settings/Tailscale" title="_(Settings)_"><i class="fa fa-fw fa-cog control"></i></a></span>
                </span>
            </span>
        </td>
    </tr>
            
    {$tailscale_dashboard}
    </tbody>
    EOT;
