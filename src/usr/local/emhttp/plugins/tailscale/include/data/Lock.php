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

    $tr = $tr ?? new Translator(PLUGIN_ROOT);

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

            foreach ($tailscaleInfo->getTailscaleLockPending() as $lockHost => $lockKey) {
                if ( ! $mullvad && str_contains($lockHost, 'mullvad.ts.net')) {
                    continue;
                }

                $rows .= "<tr><td><input type='checkbox' name='#arg[]' value='{$lockKey}'></td><td>{$lockHost}</td><td>{$lockKey}</td></tr>";
            }

            $output = <<<EOT
                <table id="lockTable" class="unraid lockTable">
                    <thead>
                         <tr>
                            <th class="filter-false">&nbsp;</th>
                            <th>Name</th>
                            <th class="filter-false">Key</th>
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
    }
} catch (\Throwable $e) {
    file_put_contents("/var/log/tailscale-error.log", print_r($e, true) . PHP_EOL, FILE_APPEND);
    echo "{}";
}
