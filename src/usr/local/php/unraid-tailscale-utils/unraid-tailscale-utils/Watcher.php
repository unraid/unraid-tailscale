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

class Watcher
{
    private Config $config;

    public function __construct()
    {
        $this->config = new Config();
    }

    public function run(): void
    {
        $timer   = 15;
        $need_ip = true;

        $tsName = '';

        if ( ! defined(__NAMESPACE__ . '\PLUGIN_ROOT') || ! defined(__NAMESPACE__ . '\PLUGIN_NAME')) {
            throw new \RuntimeException("Common file not loaded.");
        }
        $utils = new Utils(PLUGIN_NAME);

        $utils->logmsg("Starting tailscale-watcher");

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            unset($tailscale_ipv4);

            $interfaces = net_get_interfaces();

            if (isset($interfaces["tailscale1"]["unicast"])) {
                foreach ($interfaces["tailscale1"]["unicast"] as $interface) {
                    if (isset($interface["address"])) {
                        if ($interface["family"] == 2) {
                            $tailscale_ipv4 = $interface["address"];
                            $timer          = 60;
                        }
                    }
                }
            }

            if (isset($tailscale_ipv4)) {
                if ($need_ip) {
                    $utils->logmsg("Tailscale IP detected, applying configuration");
                    $need_ip = false;

                    $localAPI = new LocalAPI();
                    $status   = $localAPI->getStatus();
                    $tsName   = $status->Self->DNSName;

                    $utils->run_task('Tailscale\System::applyTailscaleConfig', array($this->config));
                    $utils->run_task('Tailscale\System::applyGRO');
                    $utils->run_task('Tailscale\System::restartSystemServices', array($this->config));
                }

                $utils->run_task('Tailscale\System::checkWebgui', array($this->config, $tailscale_ipv4));
                $utils->run_task('Tailscale\System::checkServeConfig');
                $utils->run_task('Tailscale\System::fixLocalSubnetRoutes');

                // Watch for changes to the DNS name (e.g., if someone changes the tailnet name or the Tailscale name of the server via the admin console)
                // If a change happens, refresh the Tailscale WebGUI certificate
                $localAPI  = new LocalAPI();
                $status    = $localAPI->getStatus();
                $newTsName = $status->Self->DNSName;

                if ($newTsName != $tsName) {
                    $utils->logmsg("Detected DNS name change");
                    $tsName = $newTsName;

                    $utils->run_task('Tailscale\System::refreshWebGuiCert');
                }
            } else {
                $utils->logmsg("Waiting for Tailscale IP");
            }

            sleep($timer);
        }
    }
}
