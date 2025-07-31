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

/**
* @param array<string, mixed> $params
*/
function getPage(string $filename, bool $niceError = true, array $params = array()): string
{
    try {
        require_once dirname(__FILE__) . "/common.php";
        return includePage(dirname(__FILE__) . "/Pages/{$filename}.php", $params);
    } catch (\Throwable $e) {
        if ($niceError) {
            file_put_contents("/var/log/tailscale-error.log", print_r($e, true) . PHP_EOL, FILE_APPEND);
            return includePage(dirname(__FILE__) . "/Pages/Error.php", array("e" => $e));
        } else {
            throw $e;
        }
    }
}

/**
* @param array<string, mixed> $params
*/
function includePage(string $filename, array $params = array()): string
{
    extract($params);

    if (is_file($filename)) {
        ob_start();
        include $filename;
        return ob_get_clean() ?: "";
    }
    return "";
}
