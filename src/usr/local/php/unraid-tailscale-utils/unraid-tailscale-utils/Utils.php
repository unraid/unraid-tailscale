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

use PhpIP\IPBlock;
use EDACerton\PluginUtils\Translator;

class Utils extends \EDACerton\PluginUtils\Utils
{
    public function setPHPDebug(): void
    {
        $debug = file_exists("/boot/config/plugins/tailscale/debug");

        if ($debug && ! defined("PLUGIN_DEBUG")) {
            error_reporting(E_ALL);
            define("PLUGIN_DEBUG", true);
        }
    }

    public static function printRow(string $title, string $value): string
    {
        return "<tr><td>{$title}</td><td>{$value}</td></tr>" . PHP_EOL;
    }

    public static function printDash(string $title, string $value): string
    {
        return "<tr><td><span class='w26'>{$title}</span>{$value}</td></tr>" . PHP_EOL;
    }

    public static function formatWarning(?Warning $warning): string
    {
        if ($warning == null) {
            return "";
        }

        return "<span class='{$warning->Priority}' style='text-align: center; font-size: 1.4em; font-weight: bold;'>" . $warning->Message . "</span>";
    }

    public static function ip4_in_network(string $ip, string $network): bool
    {
        if (strpos($network, '/') === false) {
            return false;
        }

        list($subnet, $mask) = explode('/', $network, 2);
        $ip_bin_string       = sprintf("%032b", ip2long($ip));
        $net_bin_string      = sprintf("%032b", ip2long($subnet));

        return (substr_compare($ip_bin_string, $net_bin_string, 0, intval($mask)) === 0);
    }

    public static function logwrap(string $message, bool $debug = false, bool $rateLimit = false): void
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        $utils = new Utils(PLUGIN_NAME);
        $utils->logmsg($message, $debug, $rateLimit);
    }

    /**
     * @return array<string>
     */
    public static function runwrap(string $command, bool $alwaysShow = false, bool $show = true): array
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("PLUGIN_NAME is not defined.");
        }
        $utils = new Utils(PLUGIN_NAME);
        return $utils->run_command($command, $alwaysShow, $show);
    }

    public static function validateCidr(string $cidr): bool
    {
        try {
            $block = IPBlock::create($cidr);

            // Check that the IP address is the network address (host bits are zero)
            return $block->getNetworkAddress()->humanReadable() . '/' . $block->getPrefixLength() === $cidr;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array<string>
     */
    public static function getExitRoutes(): array
    {
        return ["0.0.0.0/0", "::/0"];
    }

    public static function isFunnelAllowed(): bool
    {
        $directives = ["allow 127.0.0.1;", "allow ::1;"];

        $nginxConfig = file_get_contents('/etc/nginx/nginx.conf');
        if ($nginxConfig === false) {
            return false; // Unable to read the nginx configuration file
        }

        // Search $nginxConfig for the allow directives.
        foreach ($directives as $directive) {
            if (strpos($nginxConfig, $directive) !== false) {
                return false; // Directive found, funnel not safe to use
            }
        }

        return true;
    }

    /**
     * Get a list of ports that are currently assigned to services.
     * This is a best-effort approach, especially since docker might not be running during configuration.
     *
     * @return array<int>
     */
    public function get_assigned_ports(): array
    {
        $ports    = array();
        $identCfg = parse_ini_file("/boot/config/ident.cfg", false, INI_SCANNER_RAW) ?: array();
        if (isset($identCfg['PORT'])) {
            $ports[] = intval($identCfg['PORT']);
        }
        if (isset($identCfg['PORTSSL']) && isset($identCfg['USE_SSL']) && $identCfg['USE_SSL'] === 'yes') {
            $ports[] = intval($identCfg['PORTSSL']);
        }
        if (isset($identCfg['PORTTELNET']) && isset($identCfg['USE_TELNET']) && $identCfg['USE_TELNET'] === 'yes') {
            $ports[] = intval($identCfg['PORTTELNET']);
        }
        if (isset($identCfg['PORTSSH']) && isset($identCfg['USE_SSH']) && $identCfg['USE_SSH'] === 'yes') {
            $ports[] = intval($identCfg['PORTSSH']);
        }

        // Get any open TCP ports from the system
        $netstatOutput = shell_exec("netstat -tuln | grep LISTEN");
        if ($netstatOutput) {
            $lines = explode("\n", trim($netstatOutput));
            foreach ($lines as $line) {
                if (preg_match('/:(\d+)\s+/', $line, $matches)) {
                    $port = intval($matches[1]);
                    if ($port > 0 && $port < 65536) {
                        $ports[] = $port;
                    }
                }
            }
        }

        return array_unique($ports);
    }

    public static function pageChecks(Translator $tr): bool
    {
        static $config   = null;
        static $localAPI = null;

        if ($config === null) {
            $config = new Config();
        }
        if ($localAPI === null) {
            $localAPI = new LocalAPI();
        }

        if ( ! $config->Enable) {
            echo($tr->tr("tailscale_disabled"));
            return false;
        }

        if ( ! $localAPI->isReady()) {
            echo($tr->tr("warnings.not_ready"));
            echo(<<<EOT
                <script>
                    $(function() {
                        setTimeout(function() {
                            window.location = window.location.href;
                        }, 5000);
                    });
                </script>
                EOT
            );
            return false;
        }

        return true;
    }
}
