#!/usr/bin/env sh

# Install scripts receive:
# $VERSION: the package version to be installed (`latest` by default)

PHP_VERSION=$VERSION
# If latest, we set the latest PHP version explicitly because the `php` image doesn't have `latest-cli` tag
if [ "$PHP_VERSION" = "latest" ]; then
    PHP_VERSION=8.4
fi

docker build \
    --tag boxie/composer:$VERSION \
    --build-arg PHP_VERSION=$PHP_VERSION \
    --file composer.Dockerfile \
    .
