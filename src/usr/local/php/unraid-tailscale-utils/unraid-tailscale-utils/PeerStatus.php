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

class PeerStatus
{
    public string $Name      = "";
    public string $LoginName = "";
    public bool $SharedUser  = false;

    /** @var string[] */
    public array $IP = array();

    public string $Address = "";

    public bool $Online  = false;
    public bool $Active  = false;
    public bool $Relayed = false;

    public bool $Traffic = false;
    public int $TxBytes  = 0;
    public int $RxBytes  = 0;

    public bool $ExitNodeActive    = false;
    public bool $ExitNodeAvailable = false;
    public bool $Mullvad           = false;
}
