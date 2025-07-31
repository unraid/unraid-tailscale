#!/usr/bin/php -q
<?php

namespace Tailscale;

require_once "include/common.php";
if ( ! isset($utils)) {
    throw new \Exception("Utils not initialized.");
}

$localAPI = new LocalAPI();

foreach (array_slice($argv, 1) as $key => $value) {
    $utils->logmsg("Tailnet lock: signing {$value}");
    $localAPI->postTkaSign($value);
}
