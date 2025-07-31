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

class Config
{
    public bool $IncludeInterface;
    public bool $Usage;
    public bool $IPForward;
    public bool $Enable;
    public bool $SSH;
    public bool $AllowDNS;
    public bool $AllowRoutes;
    public bool $AllowFunnel;

    public int $WgPort;
    public string $TaildropDir;

    public function __construct()
    {
        $config_file = '/boot/config/plugins/tailscale/tailscale.cfg';

        // Load configuration file
        if (file_exists($config_file)) {
            $saved_config = parse_ini_file($config_file) ?: array();
        } else {
            $saved_config = array();
        }

        $this->IncludeInterface = boolval($saved_config["INCLUDE_INTERFACE"] ?? "1");
        $this->Usage            = boolval($saved_config["USAGE"] ?? "1");
        $this->IPForward        = boolval($saved_config["SYSCTL_IP_FORWARD"] ?? "1");
        $this->Enable           = boolval($saved_config["ENABLE_TAILSCALE"] ?? "1");
        $this->SSH              = boolval($saved_config["SSH"] ?? "0");
        $this->AllowDNS         = boolval($saved_config["ACCEPT_DNS"] ?? "0");
        $this->AllowRoutes      = boolval($saved_config["ACCEPT_ROUTES"] ?? "0");
        $this->AllowFunnel      = boolval($saved_config["ALLOW_FUNNEL"] ?? "0");

        $this->WgPort = intval($saved_config["WG_PORT"] ?? "0");

        $this->TaildropDir = $saved_config["TAILDROP_DIR"] ?? "";
    }
}
