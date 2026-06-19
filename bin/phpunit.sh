#!/usr/bin/env bash
set -e

DIR=$(dirname "$(dirname "$0")")

composer dump-autoload --working-dir="$DIR" --no-interaction

"$DIR/vendor/bin/phpunit" -c "$DIR/phpunit.xml.dist" "$@"
