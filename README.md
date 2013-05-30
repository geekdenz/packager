Packager
========

Debian packager for simple deployment of any app.
This can be used to deploy custom applications easily to debian based .deb packages.
Other distributions should be possible by changing the code a bit, so please fork if you can.

Dependencies
============

  - PHP 5 (cli)
  - FPM @ http://github.com/jordansissel/fpm.git

Usage
=====

Since version 0.1.2 packager is self packaged and 
can be downloaded here: https://github.com/geekdenz/packager/tree/master/packager/deb

Clone into directory (e.g. /usr/local/packager):

    git clone git@github.com:geekdenz/packager.git /usr/local/packager

Ensure package.php is executable:

    cd /usr/local/packager
    chmod +x package.php
    
Edit your PATH environment variable and add packager's directory to it:

    export PATH=/usr/local/packager:$PATH
    
In a project you can then initiate a sub directory called packager:

    cd /your/cool/project
    mkdir packager
    
In that directory you need at least one file called config.php. Ensure it has valid PHP code in it. E.g.:

    <?php
    return array(
        'my_project_name' => array(
            //'before-package' => 'before-package.php', // gets executed after the setup and before the actual package build
            'depends' => array( // list of dependencies
                'tomcat7',
                'php5-cli',
                'postgresql-9.1-postgis',
            ),
            'description' => 'Your description you want to appear when the package is queried.',
            'files' => array(
                'relative/location/of/file/to/this/folder' => '/absolute/directory/followed_by/filename.extension',
            ),
            'license' => 'GPL v3 or other',
            //'user' => 'heuert', // optional. The user to upload the deb package
            'repository' => 'repository.test:/var/www/dists/stable/main/binary-amd64', // ssh url of repository to put this in
            //'repository' => 'uvm:/tmp',
            'url' => 'http://www.documentation.org/your_application_documentation',
            'vendor' => 'Your Organisation',
            'm' => 'your@email.com',
            's' => 'dir',
            't' => 'deb', // this can probably be rpm as well, but I haven't tried
        ),
        /*
        'another_package' => array(
        ),
         */
    );
