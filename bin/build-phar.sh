#!/bin/bash
test -f phar-composer.phar || wget -O phar-composer.phar https://github.com/clue/phar-composer/releases/download/v1.2.0/phar-composer-1.2.0.phar
rm -rf build
mkdir build
cp -r vendor build/vendor
cp -r src build/src
cp -r bin build/bin
cp composer.json build/
php -d phar.readonly=off phar-composer.phar build ./build
mv strauss-build.phar stellar-strauss.phar
