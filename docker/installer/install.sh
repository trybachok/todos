#!/bin/sh
# One-shot service: installs Bitrix on the first start and, on every start, the app.notes
# module if it is missing. Safe to re-run.
set -eu

DOC_ROOT=/var/www/html
MARKER="$DOC_ROOT/bitrix/.notes-installed"
DISTRIBUTIVE_URL=${BITRIX_DISTRIBUTIVE_URL:-https://www.1c-bitrix.ru/download/start_encode.tar.gz}

if [ ! -f "$MARKER" ]; then
    if [ -f "$DOC_ROOT/bitrix/.settings.php" ]; then
        echo "A previous Bitrix installation was interrupted." >&2
        echo "Reset the volumes and start again: docker compose down -v && docker compose up -d" >&2
        exit 1
    fi

    echo "Downloading the Bitrix trial distributive (~300 MB)..."
    curl -fsSL --retry 3 "$DISTRIBUTIVE_URL" | tar -xz -C "$DOC_ROOT"

    php -S 127.0.0.1:8000 -t "$DOC_ROOT" > /tmp/wizard-server.log 2>&1 &
    server_pid=$!
    trap 'kill "$server_pid"' EXIT
    until curl -s -o /dev/null http://127.0.0.1:8000/favicon.ico; do sleep 0.2; done

    php /opt/installer/wizard.php http://127.0.0.1:8000
    touch "$MARKER"
fi

php /opt/installer/configure.php
