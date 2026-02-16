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

enum APIMethods
{
    case GET;
    case POST;
    case PATCH;
}

class LocalAPI
{
    private const tailscaleSocket = '/var/run/tailscale/tailscaled.sock';
    private Utils $utils;

    public function __construct()
    {
        if ( ! defined(__NAMESPACE__ . "\PLUGIN_ROOT") || ! defined(__NAMESPACE__ . "\PLUGIN_NAME")) {
            throw new \RuntimeException("Common file not loaded.");
        }
        $this->utils = new Utils(PLUGIN_NAME);
    }

    private function tailscaleLocalAPI(string $url, APIMethods $method = APIMethods::GET, object $body = new \stdClass()): string
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("URL cannot be empty");
        }

        $body_encoded = json_encode($body, JSON_UNESCAPED_SLASHES);

        if ( ! $body_encoded) {
            throw new \InvalidArgumentException("Failed to encode JSON");
        }

        $ch = curl_init();

        $headers = [];

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $this::tailscaleSocket);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, "http://local-tailscaled.sock/localapi/{$url}");

        if ($method == APIMethods::POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_encoded);
            $this->utils->logmsg("Tailscale Local API: {$url} POST " . $body_encoded);
            $headers[] = "Content-Type: application/json";
        }

        if ($method == APIMethods::PATCH) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body_encoded);
            $this->utils->logmsg("Tailscale Local API: {$url} PATCH " . $body_encoded);
            $headers[] = "Content-Type: application/json";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $out       = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($out === false) {
            throw new \RuntimeException("Tailscale Local API request failed for URL: {$url}");
        }

        if ($http_code < 200 || $http_code >= 300) {
            throw new \RuntimeException("Tailscale Local API returned HTTP {$http_code} for URL: {$url}");
        }

        return strval($out);
    }

    private function decodeJSONResponse(string $response): \stdClass
    {
        $decoded = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to decode JSON response: " . json_last_error_msg());
        }

        return (object) $decoded;
    }

    public function isReady(): bool
    {
        // Check if tailscaleSocket exists
        if ( ! file_exists($this::tailscaleSocket)) {
            return false;
        }

        // Check backend state from status endpoint
        try {
            $acceptedStates = ['Running', 'NeedsLogin', 'NeedsMachineAuth', 'Stopped'];
            $status         = $this->decodeJSONResponse($this->tailscaleLocalAPI('v0/status'));
            if (isset($status->BackendState) && in_array($status->BackendState, $acceptedStates, true)) {
                return true;
            }
        } catch (\RuntimeException $e) {
            // No need to log here, as this is just a readiness check
        }

        return false;
    }

    public function getStatus(): \stdClass
    {
        try {
            return $this->decodeJSONResponse($this->tailscaleLocalAPI('v0/status'));
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get status: " . $e->getMessage());
            return new \stdClass();
        }
    }

    public function getPrefs(): \stdClass
    {
        try {
            return $this->decodeJSONResponse($this->tailscaleLocalAPI('v0/prefs'));
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get prefs: " . $e->getMessage());
            return new \stdClass();
        }
    }

    public function getTkaStatus(): \stdClass
    {
        try {
            return $this->decodeJSONResponse($this->tailscaleLocalAPI('v0/tka/status'));
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get TKA status: " . $e->getMessage());
            return new \stdClass();
        }
    }

    public function getServeConfig(): ServeConfig
    {
        try {
            return new ServeConfig($this->decodeJSONResponse($this->tailscaleLocalAPI('v0/serve-config')));
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get serve config: " . $e->getMessage());
            return new ServeConfig();
        }
    }

    public function getPacketFilterRules(): \stdClass
    {
        try {
            return $this->decodeJSONResponse($this->tailscaleLocalAPI('v0/debug-packet-filter-rules'));
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to get packet filter rules: " . $e->getMessage());
            return new \stdClass();
        }
    }

    public function setServeConfig(ServeConfig $serveConfig): void
    {
        try {
            $this->tailscaleLocalAPI("v0/serve-config", APIMethods::POST, $serveConfig->getConfig());
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to set serve config: " . $e->getMessage());
        }
    }

    public function postLoginInteractive(): void
    {
        try {
            $this->tailscaleLocalAPI('v0/login-interactive', APIMethods::POST);
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to post login interactive: " . $e->getMessage());
        }
    }

    public function patchPref(string $key, mixed $value): void
    {
        $body              = [];
        $body[$key]        = $value;
        $body["{$key}Set"] = true;

        try {
            $this->tailscaleLocalAPI('v0/prefs', APIMethods::PATCH, (object) $body);
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to patch pref {$key}: " . $e->getMessage());
        }
    }

    public function postTkaSign(string $key): void
    {
        $body = ["NodeKey" => $key];

        try {
            $this->tailscaleLocalAPI("v0/tka/sign", APIMethods::POST, (object) $body);
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to sign TKA key: " . $e->getMessage());
        }
    }

    public function expireKey(): void
    {
        try {
            $this->tailscaleLocalAPI('v0/set-expiry-sooner?expiry=0', APIMethods::POST);
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to expire key: " . $e->getMessage());
        }
    }

    public function setAutoUpdate(bool $enabled): void
    {
        $body                  = [];
        $body["AutoUpdate"]    = ["Apply" => $enabled, "Check" => $enabled];
        $body["AutoUpdateSet"] = ["ApplySet" => true, "CheckSet" => true];

        try {
            $this->tailscaleLocalAPI("v0/prefs", APIMethods::PATCH, (object) $body);
        } catch (\RuntimeException $e) {
            $this->utils->logmsg("Failed to set AutoUpdate: " . $e->getMessage());
        }
    }
}
