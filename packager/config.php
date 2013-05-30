<?php
return array(
    'php-packager' => array(
        'before-package' => 'before-package.php', // gets executed after the setup and before the actual package build
        'depends' => array( // list of dependencies
            'php5-cli',
        ),
        'description' => 'This package includes the php-packager application for easy packaging of software to deploy.',
        'files' => array(
            '*' => '/usr/local/php-packager/',
            //'packager/*' => '/usr/local/php-packager/packager/
        ),
        'license' => 'GPL v3',
        //'user' => 'heuert', // optional. The user to upload the deb package, `whoami` by default
        //'repository' => 'repository.test:/var/www/dists/stable/main/binary-amd64', // ssh url of repository to put this in
        'repository' => 'uvm:/tmp/',
        'url' => 'https://github.com/geekdenz/packager',
        'vendor' => 'Landcare Research',

        'm' => 'heuert@landcareresearch.co.nz',
        's' => 'dir',
        't' => 'deb', // this can probably be rpm as well, but I haven't tried
    ),
);
