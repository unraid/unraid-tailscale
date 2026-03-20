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

class ServeConfig
{
    private \stdClass $config;

    private const CONFIG_FILE = '/boot/config/plugins/tailscale/funnel.json';

    public function __construct(?\stdClass $config = null)
    {
        $this->config = $config ?? new \stdClass();
    }

    public function configureFunnel(string $hostname, string $port, string $target): void
    {
        // Validate the hostname
        if ( ! filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException("Invalid hostname: {$hostname}");
        }

        // Validate the port
        if ( ! is_numeric($port) || (int)$port < 1 || (int)$port > 65535) {
            throw new \InvalidArgumentException("Invalid port: {$port}");
        }

        $hostAndPort = "{$hostname}:{$port}";

        // Ensure TCP exists
        if ( ! isset($this->config->TCP)) {
            $this->config->TCP = new \stdClass();
        }
        // Ensure the specific port exists
        if ( ! isset($this->config->TCP->{$port})) {
            $this->config->TCP->{$port} = new \stdClass();
        }
        $this->config->TCP->{$port}->HTTPS = true;

        // Ensure Web exists
        if ( ! isset($this->config->Web)) {
            $this->config->Web = new \stdClass();
        }
        // Ensure the specific hostAndPort exists
        if ( ! isset($this->config->Web->{$hostAndPort})) {
            $this->config->Web->{$hostAndPort} = new \stdClass();
        }
        // Ensure Handlers exists
        if ( ! isset($this->config->Web->{$hostAndPort}->Handlers)) {
            $this->config->Web->{$hostAndPort}->Handlers = new \stdClass();
        }
        // Ensure the root handler exists
        if ( ! isset($this->config->Web->{$hostAndPort}->Handlers->{'/'})) {
            $this->config->Web->{$hostAndPort}->Handlers->{'/'} = new \stdClass();
        }
        $this->config->Web->{$hostAndPort}->Handlers->{'/'}->Proxy = $target;

        // Ensure AllowFunnel exists
        if ( ! isset($this->config->AllowFunnel)) {
            $this->config->AllowFunnel = new \stdClass();
        }
        $this->config->AllowFunnel->{$hostAndPort} = true;
    }

    public function getConfig(): \stdClass
    {
        return $this->config;
    }

    public function removeServeByPort(string $port): void
    {
        // Remove TCP configuration for the port if it exists
        if (isset($this->config->TCP->{$port})) {
            unset($this->config->TCP->{$port});
        }

        // Remove Web configurations matching this port
        if (isset($this->config->Web)) {
            foreach ($this->config->Web as $fqdn => $_) {
                if (str_ends_with($fqdn, ":{$port}")) {
                    unset($this->config->Web->{$fqdn});

                    // Also remove corresponding AllowFunnel entry
                    if (isset($this->config->AllowFunnel->{$fqdn})) {
                        unset($this->config->AllowFunnel->{$fqdn});
                    }
                }
            }
        }
    }

    public function removeFunnel(string $hostname, string $port): void
    {
        $hostAndPort = "{$hostname}:{$port}";

        // Remove from TCP if it exists
        if (isset($this->config->TCP->{$port})) {
            unset($this->config->TCP->{$port});
        }

        // Remove from Web if it exists
        if (isset($this->config->Web->{$hostAndPort})) {
            unset($this->config->Web->{$hostAndPort});
        }

        // Remove from AllowFunnel if it exists
        if (isset($this->config->AllowFunnel->{$hostAndPort})) {
            unset($this->config->AllowFunnel->{$hostAndPort});
        }
    }

    public function resetFunnel(): void
    {
        if (isset($this->config->AllowFunnel)) {
            unset($this->config->AllowFunnel);
        }
    }

    public function hasFunnel(): bool
    {
        return isset($this->config->AllowFunnel);
    }

    public function saveWebguiPort(string $port): void
    {
        if ($port != "") {
            $backupCfg = array('webgui_port' => $port);
            file_put_contents(self::CONFIG_FILE, json_encode($backupCfg));
        } else {
            if (file_exists(self::CONFIG_FILE)) {
                unlink(self::CONFIG_FILE);
            }
        }
    }

    public function getWebguiPort(): ?string
    {
        if ( ! file_exists(self::CONFIG_FILE)) {
            return null;
        }

        $backupCfg = json_decode(file_get_contents(self::CONFIG_FILE) ?: "", true);

        if ($backupCfg === null || ! is_array($backupCfg)) {
            return null;
        }

        if ( ! isset($backupCfg['webgui_port']) || ! is_string($backupCfg['webgui_port'])) {
            return null;
        }

        return $backupCfg['webgui_port'];
    }

    public function getFunnelPort(string $hostname, ?string $port = null): ?string
    {
        $serveConfig = $this->config;
        if ( ! isset($serveConfig->AllowFunnel) || ! $serveConfig->AllowFunnel) {
            return null; // Funnel not enabled
        }

        if ($port === null) {
            // Get the expected target from ident.cfg
            $identCfg = parse_ini_file("/boot/config/ident.cfg", false, INI_SCANNER_RAW) ?: array();
            if ( ! isset($identCfg['PORT'])) {
                return null; // Can't determine expected target without ident.cfg PORT
            }
            $port = $identCfg['PORT'];
        }

        return $this->searchFunnelPort($hostname, $port);
    }

    private function searchFunnelPort(string $hostname, string $port): ?string
    {
        $serveConfig    = $this->config;
        $expectedTarget = "http://localhost:" . $port;

        // Get the FQDN (DNS name without trailing dot)
        $fqdn = trim($hostname, ".");

        // Look for a funnel entry that matches our FQDN and has a corresponding Web entry
        foreach ($serveConfig->AllowFunnel as $hostAndPort => $_) {
            // Check if this entry starts with our FQDN followed by a colon and port
            if (str_starts_with($hostAndPort, $fqdn . ":")) {
                // Verify this is an "old serve" funnel by checking for a Web entry
                if (isset($serveConfig->Web->{$hostAndPort})) {
                    // Verify the target matches the expected ident.cfg target
                    if (isset($serveConfig->Web->{$hostAndPort}->Handlers->{'/'}->Proxy) && $serveConfig->Web->{$hostAndPort}->Handlers->{'/'}->Proxy === $expectedTarget) {
                        // Extract the port from the hostAndPort
                        $parts = explode(":", strval($hostAndPort));
                        if (count($parts) == 2 && is_numeric($parts[1])) {
                            return strval($parts[1]);
                        }
                    }
                }
            }
        }

        return null;
    }

    public function updateWebProxy(string $hostAndPort, string $newTarget): void
    {
        if (isset($this->config->Web->{$hostAndPort}->Handlers->{'/'}->Proxy)) {
            $this->config->Web->{$hostAndPort}->Handlers->{'/'}->Proxy = $newTarget;
        }
    }
}
