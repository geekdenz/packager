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
