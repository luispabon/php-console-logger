#!/usr/bin/env bash

# Ensure we exit with failure if anything here fails
set -e

INITIAL_FOLDER=`pwd`

# cd into the codebase, as per CI source
cd code
mkdir reports

# Determine php version
PHP_VERSION=$(php -r "echo preg_replace('/.[0-9]+(-.*)?$/', '', phpversion());")
echo "Detected PHP Version: ${PHP_VERSION}"

# Downgrade composer to v1 if we're on php 7.3 (fixes infection requiring package-versions which requires php 7.4 if we're on composer 2)
if [[ "${PHP_VERSION}" == "7.3" ]]; then
    echo "Downgrading composer to v1.x because we're on php 7.3"
    composer self-update --1
fi

# Install xdebug & disable
apt-get update
apt-get install -y php${PHP_VERSION}-xdebug make
phpdismod xdebug

composer -o install

# Static analysis, unit tests
make all -e XDEBUG_MODE=coverage

# Go back to initial working dir to allow outputs to function
cd ${INITIAL_FOLDER}

# Copy reports to output (only of output is defined)
[ -d "coverage-reports"  ] && cp code/reports/* coverage-reports/ -Rf || exit 0
