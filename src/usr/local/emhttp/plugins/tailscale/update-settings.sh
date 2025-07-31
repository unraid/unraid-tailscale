#!/bin/bash

. /usr/local/emhttp/plugins/tailscale/log.sh

TS_PLUGIN_CONFIG=/boot/config/plugins/tailscale/tailscale.cfg
TS_PLUGIN_ROOT=/usr/local/emhttp/plugins/tailscale

# Cleanup Taildrop emulation from pre-1.42.0
if [ ! -s /etc/config/uLinux.conf ] && [ -f /etc/config/uLinux.conf ]; then
    log "Cleaning up Taildrop emulation"
    rm /etc/config/uLinux.conf
fi

if [ -f $TS_PLUGIN_CONFIG ]; then
    source $TS_PLUGIN_CONFIG
fi

if [[ $TAILDROP_DIR && -d "$TAILDROP_DIR" && -x "$TAILDROP_DIR" ]]; then
    log "Configuring Taildrop link"

    if [ ! -d "/var/lib/tailscale" ]; then
        mkdir /var/lib/tailscale
    fi

    ln -sfn "$TAILDROP_DIR" /var/lib/tailscale/Taildrop
fi

log "Running pre-startup script"
$TS_PLUGIN_ROOT/pre-startup.php

