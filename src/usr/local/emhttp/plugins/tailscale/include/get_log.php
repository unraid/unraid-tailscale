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

function getLog(string $log, int $max): void
{
    $allowed_files = ["/var/log/tailscale.log", "/var/log/tailscale-utils.log"];

    if ( ! in_array($log, $allowed_files)) {
        return;
    }

    if ( ! file_exists($log)) {
        echo '<span class="text">', htmlspecialchars($log), " not found.</span>";
        return;
    }

    $lines = array_reverse(array_slice(file($log) ?: array(), -$max));

    foreach ($lines as $line) {
        echo '<span class="text">', htmlspecialchars($line), "</span>";
    }
}

ini_set('memory_limit', '512M'); // Increase memory limit

try {
    if ( ! is_string($_POST['log']) || ! is_numeric($_POST['max'])) {
        throw new InvalidArgumentException("Invalid input");
    }

    getLog($_POST['log'], intval($_POST['max']));
} catch (Throwable $e) {
    echo '<span class="text">', htmlspecialchars($e->getMessage()), "</span>";
}

ini_restore('memory_limit'); // Restore original memory limit
