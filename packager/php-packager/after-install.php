<?php
echo "Installing FPM...\n";
`gem install fpm`;
echo "creating symlink for easy usage...\n";
`ln -s /usr/local/php-packager/package.php /usr/bin/`;
