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

    public function __construct(string $hostname, string $port, string $target)
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

        $this->config = new \stdClass();

        $this->config->TCP                 = new \stdClass();
        $this->config->TCP->{$port}        = new \stdClass();
        $this->config->TCP->{$port}->HTTPS = true;

        $this->config->Web                                         = new \stdClass();
        $this->config->Web->{$hostAndPort}                         = new \stdClass();
        $this->config->Web->{$hostAndPort}->Handlers               = new \stdClass();
        $this->config->Web->{$hostAndPort}->Handlers->{'/'}        = new \stdClass();
        $this->config->Web->{$hostAndPort}->Handlers->{'/'}->Proxy = $target;

        $this->config->AllowFunnel                 = new \stdClass();
        $this->config->AllowFunnel->{$hostAndPort} = true;
    }

    public function getConfig(): \stdClass
    {
        return $this->config;
    }
}
