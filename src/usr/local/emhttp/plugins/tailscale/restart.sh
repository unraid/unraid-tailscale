#!/bin/bash

. /usr/local/php/unraid-tailscale-utils/log.sh

log "Restarting Tailscale in 5 seconds"
echo "sleep 5 ; /etc/rc.d/rc.tailscale restart" | at now 2>/dev/null
