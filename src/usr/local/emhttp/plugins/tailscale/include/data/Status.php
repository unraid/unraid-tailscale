<?php

namespace Tailscale;

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

use EDACerton\PluginUtils\Translator;

try {
    require_once dirname(dirname(__FILE__)) . "/common.php";

    if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
        throw new \RuntimeException("Common file not loaded.");
    }

    $tr    = $tr    ?? new Translator(PLUGIN_ROOT);
    $utils = $utils ?? new Utils(PLUGIN_NAME);

    $tailscaleConfig = $tailscaleConfig ?? new Config();

    if ( ! $tailscaleConfig->Enable) {
        echo("{}");
        return;
    }

    switch ($_POST['action']) {
        case 'get':
            $tailscaleInfo = $tailscaleInfo ?? new Info($tr);
            $rows          = "";

            $mullvad = filter_var($_POST['mullvad'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $shared  = filter_var($_POST['shared'] ?? false, FILTER_VALIDATE_BOOLEAN);

            foreach ($tailscaleInfo->getPeerStatus() as $peer) {
                if ($peer->Mullvad && ! $mullvad && ! $peer->Active) {
                    continue;
                }
                if ($peer->SharedUser && ! $shared && ! $peer->Active) {
                    continue;
                }

                $user       = $peer->SharedUser ? $tr->tr('status_page.shared') : $peer->Name;
                $online     = $peer->Online ? ($peer->Active ? $tr->tr('status_page.active') : $tr->tr('status_page.idle')) : $tr->tr('status_page.offline');
                $exitNode   = $peer->ExitNodeActive ? $tr->tr('status_page.exit_active') : ($peer->ExitNodeAvailable ? ($peer->Mullvad ? "Mullvad" : $tr->tr('status_page.exit_available')) : "");
                $connection = $peer->Active ? ($peer->Relayed ? $tr->tr('status_page.relay') : $tr->tr('status_page.direct')) : "";
                $active     = $peer->Active ? $peer->Address : "";
                $txBytes    = $peer->Traffic ? $peer->TxBytes : "";
                $rxBytes    = $peer->Traffic ? $peer->RxBytes : "";
                $pingHost   = ($peer->SharedUser || $peer->Active || ! $peer->Online || $peer->Mullvad) ? "" : "<input type='button' class='ping' value='Ping' onclick='pingHost(\"{$peer->Name}\")'>";
                $ips        = implode("<br>", $peer->IP);

                $rows .= <<<EOT
                    <tr>
                        <td>{$user}</td>
                        <td>{$ips}</td>
                        <td>{$peer->LoginName}</td>
                        <td>{$online}</td>
                        <td>{$exitNode}</td>
                        <td>{$connection}</td>
                        <td>{$active}</td>
                        <td>{$txBytes}</td>
                        <td>{$rxBytes}</td>
                        <td>{$pingHost}</td>
                    </tr>
                    EOT;
            }

            $output = <<<EOT
                <table id="statusTable" class="unraid statusTable">
                    <thead>
                        <tr>
                            <th>{$tr->tr('info.dns')}</th>
                            <th>{$tr->tr('info.ip')}</th>
                            <th>{$tr->tr('status_page.login_name')}</th>
                            <th class="filter-select filter-match" id="status">{$tr->tr('status')}</th>
                            <th class="filter-select filter-match" id="exitnode">{$tr->tr('status_page.exit_node')}</th>
                            <th class="filter-select filter-match" id="conntype">{$tr->tr('status_page.connection_type')}</th>
                            <th class="filter-false">{$tr->tr('status_page.connection_addr')}</th>
                            <th class="filter-false">{$tr->tr('status_page.tx_bytes')}</th>
                            <th class="filter-false">{$tr->tr('status_page.rx_bytes')}</th>
                            <th class="filter-false">{$tr->tr('status_page.action')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
                EOT;

            $rtn         = array();
            $rtn['html'] = $output;
            echo json_encode($rtn);
            break;
        case 'ping':
            $tailscaleInfo = $tailscaleInfo ?? new Info($tr);
            $out           = "Could not find host.";

            foreach ($tailscaleInfo->getPeerStatus() as $peer) {
                if ($peer->Name == $_POST['host']) {
                    $peerIP = escapeshellarg($peer->IP[0]);
                    $out    = implode("<br>", $utils->run_command("tailscale ping {$peerIP}"));
                    break;
                }
            }

            echo $out;
            break;
    }
} catch (\Throwable $e) {
    file_put_contents("/var/log/tailscale-error.log", print_r($e, true) . PHP_EOL, FILE_APPEND);
    echo "{}";
}
