#!/bin/zsh
# Run WP-CLI against the SBOTFAM Local site using Local's own PHP. Usage: ./wpl.sh plugin list
L="$HOME/Library/Application Support/Local"
exec "$L/lightning-services/php-8.5.3+1/bin/darwin/bin/php" -d mysqli.default_socket="$L/run/48ZlbwRuP/mysql/mysqld.sock" -d memory_limit=512M -d error_reporting=24575 /Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar --path="$HOME/Local Sites/sbotfam/app/public" "$@"
