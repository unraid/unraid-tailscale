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

    $localAPI      = new LocalAPI();
    $tailscaleInfo = $tailscaleInfo ?? new Info($tr);

    switch ($_POST['action']) {
        case 'get':
            $connectionRows = "";
            $configRows     = "";
            $routes         = "<table id='routesTable' class='unraid statusTable'></table>";
            $config         = "<table id='configTable' class='unraid statusTable'></table>";

            if ($tailscaleInfo->needsLogin()) {
                $connectionRows = "<tr><td>{$tr->tr("needs_login")}</td><td><input type='button' value='{$tr->tr("login")}' onclick='tailscaleUp()'></td><td><a id='tailscaleUpLink' href='#'></a></td></tr>";
            } else {
                $tailscaleStatusInfo = $tailscaleInfo->getStatusInfo();
                $tailscaleConInfo    = $tailscaleInfo->getConnectionInfo();

                $acceptDNSButton = $tailscaleInfo->acceptsDNS() ? "<input type='button' value='{$tr->tr("disable")}' onclick='setFeature(\"dns\", false)'>" :
                    (
                        $tailscaleConfig->AllowDNS ? "<input type='button' value='{$tr->tr("enable")}' onclick='setFeature(\"dns\", true)'>" :
                        "<input type='button' value='{$tr->tr("enable")}' disabled>"
                    );

                $acceptRoutesButton = $tailscaleInfo->acceptsRoutes() ? "<input type='button' value='{$tr->tr("disable")}' onclick='setFeature(\"routes\", false)'>" :
                    (
                        $tailscaleConfig->AllowRoutes ? "<input type='button' value='{$tr->tr("enable")}' onclick='setFeature(\"routes\", true)'>" :
                        "<input type='button' value='{$tr->tr("enable")}' disabled>"
                    );

                $sshButton = $tailscaleInfo->runsSSH() ?
                    "<input type='button' value='{$tr->tr("disable")}' onclick='setFeature(\"ssh\", false)'>" :
                    "<input type='button' value='{$tr->tr("enable")}' onclick='setFeature(\"ssh\", true)'>";

                $advertiseExitButton = $tailscaleInfo->usesExitNode() ? "<input type='button' value='{$tr->tr("enable")}' disabled>" :
                    (
                        $tailscaleInfo->advertisesExitNode() ?
                        "<input type='button' value='{$tr->tr("disable")}' onclick='setAdvertiseExitNode(false)'>" :
                        "<input type='button' value='{$tr->tr("enable")}' onclick='setAdvertiseExitNode(true)'>"
                    );

                $exitLocalButton = $tailscaleInfo->exitNodeLocalAccess() ?
                    "<input type='button' value='{$tr->tr("disable")}' onclick='setFeature(\"exit-allow-local\", false)'>" :
                    "<input type='button' value='{$tr->tr("enable")}' onclick='setFeature(\"exit-allow-local\", true)'>";

                $connectionRows = <<<EOT
                    <tr><td>{$tr->tr("info.hostname")}</td><td>{$tailscaleConInfo->HostName}</td><td></td></tr>
                    <tr><td>{$tr->tr("info.dns")}</td><td>{$tailscaleConInfo->DNSName}</td><td></td></tr>
                    <tr><td>{$tr->tr("info.ip")}</td><td>{$tailscaleConInfo->TailscaleIPs}</td><td></td></tr>
                    <tr><td>{$tr->tr("info.magicdns")}</td><td>{$tailscaleConInfo->MagicDNSSuffix}</td><td></td></tr>
                    <tr><td>{$tr->tr("tailnet")}</td><td>{$tailscaleInfo->getTailnetName()}</td><td></td></tr>
                    EOT;

                $exitDisabled = $tailscaleInfo->advertisesExitNode() ? "disabled" : "";
                $currentExit  = $tailscaleInfo->getCurrentExitNode();

                $exitSelect = "<select id='exitNodeSelect' onchange='setTailscaleExitNode()' style='width: 100%' {$exitDisabled}><option value=''>{$tr->tr("none")}</option>";
                foreach ($tailscaleInfo->getExitNodes() as $node => $name) {
                    $selected = $node == $currentExit ? "selected" : "";
                    $exitSelect .= "<option value='{$node}' {$selected}>{$name}</option>";
                }
                $exitSelect .= "</select>";

                $configRows = <<<EOT
                    <tr><td>{$tr->tr("info.accept_routes")}</td><td>{$tailscaleConInfo->AcceptRoutes}</td><td style="text-align: right;">{$acceptRoutesButton}</td></tr>
                    <tr><td>{$tr->tr("info.accept_dns")}</td><td>{$tailscaleConInfo->AcceptDNS}</td><td style="text-align: right;">{$acceptDNSButton}</td></tr>
                    <tr><td>{$tr->tr("info.run_ssh")}</td><td>{$tailscaleConInfo->RunSSH}</td><td style="text-align: right;">{$sshButton}</td></tr>
                    <tr><td>{$tr->tr("info.advertise_exit_node")}</td><td>{$tailscaleConInfo->AdvertiseExitNode}</td><td style="text-align: right;">{$advertiseExitButton}</td></tr>
                    <tr><td>{$tr->tr("info.use_exit_node")}</td><td>&nbsp;</td><td style="text-align: right;">{$exitSelect}</td></tr>
                    <tr><td>{$tr->tr("info.exit_node_local")}</td><td>{$tailscaleConInfo->ExitNodeLocal}</td><td style="text-align: right;">{$exitLocalButton}</td></tr>

                    EOT;

                if (Utils::isFunnelAllowed() && $tailscaleConfig->AllowFunnel) {
                    // Create a list of ports similar to the one used by the exit node selection.
                    // Available ports can be obtained with $tailscaleInfo->getAllowedFunnelPorts
                    // Any port that is returned by Utils::get_assigned_ports should not be selectable
                    $funnelPorts   = $tailscaleInfo->getAllowedFunnelPorts();
                    $assignedPorts = $utils->get_assigned_ports();

                    $utils->logmsg("Funnel ports: " . implode(", ", $funnelPorts));
                    $utils->logmsg("Assigned ports: " . implode(", ", $assignedPorts));

                    $funnelSelect = "<select id='funnelPortSelect' onchange='setFunnelPort()' style='width: 100%'>";
                    $funnelSelect .= "<option value=''>{$tr->tr("none")}</option>";

                    foreach ($funnelPorts as $port) {
                        $currentPort = $tailscaleInfo->getFunnelPort();
                        $selected    = $currentPort == $port ? "selected" : "";
                        $disablePort = ( ! in_array($port, $assignedPorts) || $port == $currentPort);

                        $disableAttr = $disablePort ? "" : ' disabled="disabled"';
                        $disableText = $disablePort ? "" : " ({$tr->tr("info.port_in_use")})";

                        $funnelSelect .= "<option value='{$port}' {$selected} {$disableAttr}>{$port} {$disableText}</option>";
                    }
                    $funnelSelect .= "</select>";
                    $configRows   .= <<<EOT
                        <tr><td>{$tr->tr("info.funnel_port")}</td><td>&nbsp;</td><td style="text-align: right;">{$funnelSelect}</td></tr>
                        EOT;
                }

                $routesRows = "";

                foreach ($tailscaleInfo->getAdvertisedRoutes() as $route) {
                    $approved = $tailscaleInfo->isApprovedRoute($route) ? "" : $tr->tr("info.unapproved");
                    $routesRows .= "<tr><td>{$route}</td><td>{$approved}</td><td style='text-align: right;'><input type='button' value='{$tr->tr("remove")}' onclick='removeTailscaleRoute(\"{$route}\")'></td></tr>";
                }

                $routes = <<<EOT
                    <table id="routesTable" class="unraid statusTable">
                        <thead>
                            <tr>
                                <th style="width: 40%" class="filter-false">{$tr->tr('info.routes')}</th>
                                <th style="width: 40%" class="filter-false">&nbsp;</th>
                                <th class="filter-false">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$routesRows}
                            <tr><td><input type="text" id="tailscaleRoute" name="tailscaleRoute" oninput='validateTailscaleRoute()'></td><td>&nbsp;</td><td style="text-align: right;"><input type='button' id="addTailscaleRoute" value='{$tr->tr("add")}' onclick='addTailscaleRoute()'></td></tr>
                        </tbody>
                    </table>
                    EOT;

                $config = <<<EOT
                    <table id="configTable" class="unraid statusTable">
                        <thead>
                            <tr>
                                <th style="width: 40%" class="filter-false">{$tr->tr('configuration')}</th>
                                <th style="width: 40%" class="filter-false">&nbsp;</th>
                                <th class="filter-false">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$configRows}
                        </tbody>
                    </table>
                    EOT;
            }

            $connection = <<<EOT
                <table id="connectionTable" class="unraid statusTable">
                    <thead>
                        <tr>
                            <th style="width: 40%" class="filter-false">{$tr->tr('connection')}</th>
                            <th style="width: 40%" class="filter-false">&nbsp;</th>
                            <th class="filter-false">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$connectionRows}
                    </tbody>
                </table>
                EOT;

            $rtn               = array();
            $rtn['config']     = $config;
            $rtn['routes']     = $routes;
            $rtn['connection'] = $connection;

            echo json_encode($rtn);
            break;
        case 'set-feature':
            $features = [
                'dns'              => 'CorpDNS',
                'routes'           => 'RouteAll',
                'ssh'              => 'RunSSH',
                'exit-allow-local' => 'ExitNodeAllowLANAccess'
            ];

            if ( ! (isset($features[$_POST['feature']]))) {
                throw new \Exception("Invalid feature: {$_POST['feature']}");
            }

            if ( ! isset($_POST['enable'])) {
                throw new \Exception("Missing enable parameter");
            }

            $enable = filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN);
            $utils->logmsg("Setting feature: {$features[$_POST['feature']]} to " . ($enable ? "true" : "false"));

            $localAPI->patchPref($features[$_POST['feature']], $enable);
            break;
        case 'set-advertise-exit-node':
            if ( ! isset($_POST['enable'])) {
                throw new \Exception("Missing enable parameter");
            }

            $enable = filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN);
            $utils->logmsg("Setting advertise exit node to " . ($enable ? "true" : "false"));

            $prefs      = $localAPI->getPrefs();
            $routes     = $prefs->AdvertiseRoutes ?? array();
            $exitRoutes = Utils::getExitRoutes();

            if ($enable) {
                $routes = array_unique(array_merge($routes, $exitRoutes));
            } else {
                $routes = array_diff($routes, $exitRoutes);
            }

            $localAPI->patchPref("AdvertiseRoutes", array_values($routes));
            break;
        case 'up':
            $utils->logmsg("Getting Auth URL");
            $authURL = $tailscaleInfo->getAuthURL();
            if ($authURL == "") {
                $localAPI->postLoginInteractive();
                $retries = 0;
                while ($retries < 60) {
                    $tailscaleInfo = new Info($tr);
                    $authURL       = $tailscaleInfo->getAuthURL();
                    if ($authURL != "") {
                        break;
                    }
                    usleep(500000);
                    $retries++;
                }
            }
            echo $authURL;
            break;
        case 'remove-route':
            if ( ! isset($_POST['route'])) {
                throw new \Exception("Missing route parameter");
            }

            $utils->logmsg("Removing route: {$_POST['route']}");

            $advertisedRoutes = $tailscaleInfo->getAdvertisedRoutes();
            $advertisedRoutes = array_diff($advertisedRoutes, [$_POST['route']]);

            $localAPI->patchPref("AdvertiseRoutes", array_values($advertisedRoutes));
            break;
        case 'add-route':
            if ( ! isset($_POST['route'])) {
                throw new \Exception("Missing route parameter");
            }

            if ( ! Utils::validateCidr($_POST['route'])) {
                throw new \Exception("Invalid route: {$_POST['route']}");
            }

            $utils->logmsg("Adding route: {$_POST['route']}");

            $advertisedRoutes   = $tailscaleInfo->getAdvertisedRoutes();
            $advertisedRoutes[] = $_POST['route'];

            $localAPI->patchPref("AdvertiseRoutes", array_values($advertisedRoutes));
            break;
        case 'expire-key':
            if ($tailscaleInfo->connectedViaTS()) {
                throw new \Exception("Cannot expire key while connected via Tailscale");
            }
            $utils->logmsg("Expiring node key");
            $localAPI->expireKey();
            break;
        case 'exit-node':
            if ( ! isset($_POST['node'])) {
                throw new \Exception("Missing node parameter");
            }

            $exitNodes = $tailscaleInfo->getExitNodes();
            if (( ! isset($exitNodes[$_POST['node']])) && ($_POST['node'] != '')) {
                throw new \Exception("Invalid node parameter");
            }

            $utils->logmsg("Setting exit node: {$_POST['node']}");

            $localAPI->patchPref("ExitNodeID", $_POST['node']);
            break;
        case 'funnel-port':
            if ( ! isset($_POST['port'])) {
                throw new \Exception("Missing port parameter");
            }

            // If port is empty, reset the serve config, this disables funnel
            if ($_POST['port'] == '') {
                $utils->logmsg("Resetting funnel port");
                $localAPI->resetServeConfig();
                break;
            }

            $identCfg = parse_ini_file("/boot/config/ident.cfg", false, INI_SCANNER_RAW) ?: array();
            if ( ! isset($identCfg['PORT'])) {
                throw new \Exception("Ident configuration does not contain PORT");
            }

            $serveConfig = new ServeConfig(
                trim($tailscaleInfo->getDNSName(), "."),
                $_POST['port'],
                "http://localhost:" . $identCfg['PORT']
            );

            $utils->logmsg("Object: " . json_encode($serveConfig->getConfig(), JSON_UNESCAPED_SLASHES));
            $localAPI->setServeConfig($serveConfig);
            break;
    }
} catch (\Throwable $e) {
    file_put_contents("/var/log/tailscale-error.log", print_r($e, true) . PHP_EOL, FILE_APPEND);
    echo "{}";
}
