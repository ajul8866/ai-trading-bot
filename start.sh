#!/usr/bin/env bash

# Wait a bit for MySQL to be ready
sleep 5

# Start supervisord (it will start PHP serve, queue worker, and scheduler)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
