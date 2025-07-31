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

class Info
{
    private string $useNetbios;
    private string $smbEnabled;
    private Translator $tr;
    private LocalAPI $localAPI;
    private \stdClass $status;
    private \stdClass $prefs;
    private \stdClass $lock;
    private \stdClass $serve;

    public function __construct(Translator $tr)
    {
        $share_config = parse_ini_file("/boot/config/share.cfg") ?: array();
        $ident_config = parse_ini_file("/boot/config/ident.cfg") ?: array();

        $this->localAPI = new LocalAPI();

        $this->tr         = $tr;
        $this->smbEnabled = $share_config['shareSMBEnabled'] ?? "";
        $this->useNetbios = $ident_config['USE_NETBIOS']     ?? "";
        $this->status     = $this->localAPI->getStatus();
        $this->prefs      = $this->localAPI->getPrefs();
        $this->lock       = $this->localAPI->getTkaStatus();
        $this->serve      = $this->localAPI->getServeConfig();
    }

    public function getStatus(): \stdClass
    {
        return $this->status;
    }

    public function getPrefs(): \stdClass
    {
        return $this->prefs;
    }

    public function getLock(): \stdClass
    {
        return $this->lock;
    }

    private function tr(string $message): string
    {
        return $this->tr->tr($message);
    }

    public function getStatusInfo(): StatusInfo
    {
        $status = $this->status;
        $prefs  = $this->prefs;
        $lock   = $this->lock;

        $statusInfo = new StatusInfo();

        $statusInfo->TsVersion     = isset($status->Version) ? $status->Version : $this->tr("unknown");
        $statusInfo->KeyExpiration = isset($status->Self->KeyExpiry) ? $status->Self->KeyExpiry : $this->tr("disabled");
        $statusInfo->Online        = isset($status->Self->Online) ? ($status->Self->Online ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $statusInfo->InNetMap      = isset($status->Self->InNetworkMap) ? ($status->Self->InNetworkMap ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $statusInfo->Tags          = isset($status->Self->Tags) ? implode("<br>", $status->Self->Tags) : "";
        $statusInfo->LoggedIn      = isset($prefs->LoggedOut) ? ($prefs->LoggedOut ? $this->tr("no") : $this->tr("yes")) : $this->tr("unknown");
        $statusInfo->TsHealth      = isset($status->Health) ? implode("<br>", $status->Health) : "";
        $statusInfo->LockEnabled   = $this->getTailscaleLockEnabled() ? $this->tr("yes") : $this->tr("no");

        if ($this->getTailscaleLockEnabled()) {
            $lockInfo = new LockInfo();

            $lockInfo->LockSigned  = $this->getTailscaleLockSigned() ? $this->tr("yes") : $this->tr("no");
            $lockInfo->LockSigning = $this->getTailscaleLockSigning() ? $this->tr("yes") : $this->tr("no");
            $lockInfo->PubKey      = $this->getTailscaleLockPubkey();
            $lockInfo->NodeKey     = $this->getTailscaleLockNodekey();

            $statusInfo->LockInfo = $lockInfo;
        }

        return $statusInfo;
    }

    public function getConnectionInfo(): ConnectionInfo
    {
        $status = $this->status;
        $prefs  = $this->prefs;

        $info = new ConnectionInfo();

        $info->HostName         = isset($status->Self->HostName) ? $status->Self->HostName : $this->tr("unknown");
        $info->DNSName          = isset($status->Self->DNSName) ? $status->Self->DNSName : $this->tr("unknown");
        $info->TailscaleIPs     = isset($status->TailscaleIPs) ? implode("<br>", $status->TailscaleIPs) : $this->tr("unknown");
        $info->MagicDNSSuffix   = isset($status->MagicDNSSuffix) ? $status->MagicDNSSuffix : $this->tr("unknown");
        $info->AdvertisedRoutes = isset($prefs->AdvertiseRoutes) ? implode("<br>", $prefs->AdvertiseRoutes) : $this->tr("none");
        $info->AcceptRoutes     = isset($prefs->RouteAll) ? ($prefs->RouteAll ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $info->AcceptDNS        = isset($prefs->CorpDNS) ? ($prefs->CorpDNS ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $info->RunSSH           = isset($prefs->RunSSH) ? ($prefs->RunSSH ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $info->ExitNodeLocal    = isset($prefs->ExitNodeAllowLANAccess) ? ($prefs->ExitNodeAllowLANAccess ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");
        $info->UseExitNode      = $this->usesExitNode() ? $this->tr("yes") : $this->tr("no");

        if ($this->advertisesExitNode()) {
            if ($this->status->Self->ExitNodeOption) {
                $info->AdvertiseExitNode = $this->tr("yes");
            } else {
                $info->AdvertiseExitNode = $this->tr("info.unapproved");
            }
        } else {
            $info->AdvertiseExitNode = $this->tr("no");
        }

        return $info;
    }

    public function getDashboardInfo(): DashboardInfo
    {
        $status = $this->status;

        $info = new DashboardInfo();

        $info->HostName     = isset($status->Self->HostName) ? $status->Self->HostName : $this->tr("Unknown");
        $info->DNSName      = isset($status->Self->DNSName) ? $status->Self->DNSName : $this->tr("Unknown");
        $info->TailscaleIPs = isset($status->TailscaleIPs) ? $status->TailscaleIPs : array();
        $info->Online       = isset($status->Self->Online) ? ($status->Self->Online ? $this->tr("yes") : $this->tr("no")) : $this->tr("unknown");

        return $info;
    }

    public function getKeyExpirationWarning(): ?Warning
    {
        $status = $this->status;

        if (isset($status->Self->KeyExpiry)) {
            $expiryTime = new \DateTime($status->Self->KeyExpiry);
            $expiryTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            $interval      = $expiryTime->diff(new \DateTime('now'));
            $expiryPrint   = $expiryTime->format(\DateTimeInterface::RFC7231);
            $intervalPrint = $interval->format('%a');

            $warning = new Warning(sprintf($this->tr("warnings.key_expiration"), $intervalPrint, $expiryPrint));

            switch (true) {
                case $interval->days <= 7:
                    $warning->Priority = 'error';
                    break;
                case $interval->days <= 30:
                    $warning->Priority = 'warn';
                    break;
                default:
                    $warning->Priority = 'system';
                    break;
            }

            return $warning;
        }
        return null;
    }

    public function getTailscaleLockEnabled(): bool
    {
        return $this->lock->Enabled ?? false;
    }

    public function getTailscaleLockSigned(): bool
    {
        if ( ! $this->getTailscaleLockEnabled()) {
            return false;
        }

        return $this->lock->NodeKeySigned;
    }

    public function getTailscaleLockNodekey(): string
    {
        if ( ! $this->getTailscaleLockEnabled()) {
            return "";
        }

        return $this->lock->NodeKey;
    }

    public function getTailscaleLockPubkey(): string
    {
        if ( ! $this->getTailscaleLockEnabled()) {
            return "";
        }

        return $this->lock->PublicKey;
    }

    public function getTailscaleLockSigning(): bool
    {
        if ( ! $this->getTailscaleLockSigned()) {
            return false;
        }

        $isTrusted = false;
        $myKey     = $this->getTailscaleLockPubkey();

        foreach ($this->lock->TrustedKeys as $item) {
            if ($item->Key == $myKey) {
                $isTrusted = true;
            }
        }

        return $isTrusted;
    }

    /**
     * @return array<string, string>
     */
    public function getTailscaleLockPending(): array
    {
        if ( ! $this->getTailscaleLockSigning()) {
            return array();
        }

        $pending = array();

        foreach ($this->lock->FilteredPeers as $item) {
            $pending[$item->Name] = $item->NodeKey;
        }

        return $pending;
    }

    public function getTailscaleLockWarning(): ?Warning
    {
        if ($this->getTailscaleLockEnabled() && ( ! $this->getTailscaleLockSigned())) {
            return new Warning($this->tr("warnings.lock"), "error");
        }
        return null;
    }

    public function getTailscaleNetbiosWarning(): ?Warning
    {
        if (($this->useNetbios == "yes") && ($this->smbEnabled != "no")) {
            return new Warning($this->tr("warnings.netbios"), "warn");
        }
        return null;
    }

    /**
     * @return array<int, PeerStatus>
     */
    public function getPeerStatus(): array
    {
        $result = array();

        foreach ($this->status->Peer as $node => $status) {
            $peer = new PeerStatus();

            $peer->Name = trim($status->DNSName, ".");
            $peer->IP   = $status->TailscaleIPs;

            $peer->LoginName  = $this->status->User->{$status->UserID}->LoginName;
            $peer->SharedUser = isset($status->ShareeNode);

            if ($status->ExitNode) {
                $peer->ExitNodeActive = true;
            } elseif ($status->ExitNodeOption) {
                $peer->ExitNodeAvailable = true;
            }
            $peer->Mullvad = in_array("tag:mullvad-exit-node", $status->Tags ?? array());

            if ($status->TxBytes > 0 || $status->RxBytes > 0) {
                $peer->Traffic = true;
                $peer->TxBytes = $status->TxBytes;
                $peer->RxBytes = $status->RxBytes;
            }

            if ( ! $status->Online) {
                $peer->Online = false;
                $peer->Active = false;
            } elseif ( ! $status->Active) {
                $peer->Online = true;
                $peer->Active = false;
            } else {
                $peer->Online = true;
                $peer->Active = true;

                if (($status->Relay != "") && ($status->CurAddr == "")) {
                    $peer->Relayed = true;
                    $peer->Address = $status->Relay;
                } elseif ($status->CurAddr != "") {
                    $peer->Relayed = false;
                    $peer->Address = $status->CurAddr;
                }
            }

            $result[] = $peer;
        }

        return $result;
    }

    public function advertisesExitNode(): bool
    {
        foreach (($this->prefs->AdvertiseRoutes ?? array()) as $net) {
            switch ($net) {
                case "0.0.0.0/0":
                case "::/0":
                    return true;
            }
        }

        return false;
    }

    public function usesExitNode(): bool
    {
        if (($this->prefs->ExitNodeID ?? "") || ($this->prefs->ExitNodeIP ?? "")) {
            return true;
        }
        return false;
    }

    public function exitNodeLocalAccess(): bool
    {
        return $this->prefs->ExitNodeAllowLANAccess ?? false;
    }

    public function acceptsDNS(): bool
    {
        return $this->prefs->CorpDNS ?? false;
    }

    public function acceptsRoutes(): bool
    {
        return $this->prefs->RouteAll ?? false;
    }

    public function runsSSH(): bool
    {
        return $this->prefs->RunSSH ?? false;
    }

    public function isOnline(): bool
    {
        return $this->status->Self->Online ?? false;
    }

    public function getAuthURL(): string
    {
        return $this->status->AuthURL ?? "";
    }

    public function needsLogin(): bool
    {
        return ($this->status->BackendState ?? "") == "NeedsLogin";
    }

    /**
     * @return array<string>
     */
    public function getAdvertisedRoutes(): array
    {
        $advertisedRoutes = $this->prefs->AdvertiseRoutes ?? array();
        $exitNodeRoutes   = ["0.0.0.0/0", "::/0"];
        return array_diff($advertisedRoutes, $exitNodeRoutes);
    }

    public function isApprovedRoute(string $route): bool
    {
        return in_array($route, $this->status->Self->AllowedIPs ?? array());
    }

    public function getTailnetName(): string
    {
        return $this->status->CurrentTailnet->Name ?? "";
    }

    /**
     * @return array<string, string>
     */
    public function getExitNodes(): array
    {
        $exitNodes = array();

        foreach (($this->status->Peer ?? array()) as $node => $status) {
            if ($status->ExitNodeOption ?? false) {
                $nodeName = $status->DNSName;
                if (isset($status->Location->City)) {
                    $nodeName .= " (" . $status->Location->City . ")";
                }
                $exitNodes[$status->ID] = $nodeName;
            }
        }

        return $exitNodes;
    }

    public function getCurrentExitNode(): string
    {
        foreach (($this->status->Peer ?? array()) as $node => $status) {
            if ($status->ExitNode ?? false) {
                return $status->ID;
            }
        }

        return "";
    }

    public function connectedViaTS(): bool
    {
        return in_array($_SERVER['SERVER_ADDR'] ?? "", $this->status->TailscaleIPs ?? array());
    }

    /**
     * @return array<int>
     */
    public function getAllowedFunnelPorts(): array
    {
        $allowedPorts = array();

        if (isset($this->status->Self->CapMap)) {
            $prefix = "https://tailscale.com/cap/funnel-ports?ports=";
            foreach ($this->status->Self->CapMap as $cap => $value) {
                if (strpos($cap, $prefix) === 0) {
                    $ports = explode(",", substr($cap, strlen($prefix)));
                    foreach ($ports as $port) {
                        $allowedPorts[] = intval($port);
                    }
                    break;
                }
            }
        }
        return $allowedPorts;
    }

    public function getFunnelPort(): ?int
    {
        if (isset($this->serve->AllowFunnel) && $this->serve->AllowFunnel) {
            $funnelKeys = array_keys((array)$this->serve->AllowFunnel);
            if (count($funnelKeys) > 0) {
                $funnelKey = $funnelKeys[0];
                $parts     = explode(":", strval($funnelKey));
                if (count($parts) == 2 && is_numeric($parts[1])) {
                    return intval($parts[1]);
                }
            }
        }

        return null; // Funnel not enabled
    }

    public function getDNSName(): string
    {
        if ( ! isset($this->status->Self->DNSName)) {
            throw new \RuntimeException("DNSName not set in Tailscale status.");
        }

        return $this->status->Self->DNSName;
    }
}
