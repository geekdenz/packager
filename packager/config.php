<?php
return array(
    'php-packager' => array(
        'before-package' => 'before-package.php', // gets executed after the setup and before the actual package build
        'depends' => array( // list of dependencies
            'php5-cli',
            'rubygems',
            'ruby1.8-dev',
        ),
        'description' => 'This package includes the php-packager application for easy packaging of software to deploy.',
        'files' => array(
            '*' => '/usr/local/php-packager/',
            //'packager/*' => '/usr/local/php-packager/packager/
        ),
        'license' => 'GPL v3',
        'user' => 'heuert', // optional. The user to upload the deb package, `whoami` by default
        //'repository' => 'ubuntu-vm:/tmp/',
        'repository' => 'repository.test.zen.landcareresearch.co.nz:/var/www/dists/precise/main/binary-amd64',
        'url' => 'https://github.com/geekdenz/packager',
        'vendor' => 'Landcare Research',

        'm' => 'heuert@landcareresearch.co.nz',
        's' => 'dir',
        't' => 'deb', // this can probably be rpm as well, but I haven't tried
    ),
);
