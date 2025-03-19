#!/usr/bin/env bash
set -e

DIR=$(dirname "$(dirname "$0")")

if [ ! -f $DIR/phpstan.neon ]; then
  php $DIR/bin/phpstan-config-generator.php
fi;

php $DIR/vendor/bin/phpstan dump-parameters
php $DIR/vendor/bin/phpstan analyze $DIR/src
