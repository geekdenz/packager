Packager
========

Debian packager for simple deployment of any app.
This can be used to deploy custom applications 
easily to debian based .deb packages.
Other distributions should be possible by 
changing the type ('t') in the configuration
file (config.php).

Dependencies
============

  - PHP 5 (cli)
  - FPM @ http://github.com/jordansissel/fpm.git

Usage
=====

Since version 0.1.2 packager is self packaged and can be 
downloaded here:
https://github.com/geekdenz/packager/tree/master/packager/deb
You can then install it by running

    sudo dpkg -i php-packager_0.1.2_amd64.deb
    
If you do not like it, it can be easily removed by calling

    sudo dpkg -r php-packager
    
We do not have a ppa for this yet, because we don't have a 
public repository. However, this should be easy to do in the 
future.

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
    
Make sure you have a file called /your/cool/project/version.txt in place with the content of '0.1.0'
for example, because packager.php assumes it is there!

In that directory you need at least one file called config.php. Ensure 
it has valid PHP code in it. E.g.:

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
    
You can add any PHP code in your 'packager/after-install.php' and 
'packager/before-remove.php' scripts. However, all code used has
to be in those files at this stage. A nice extension would be to 
make includes and requires possible, but it has been kept simple
for now. At least you can add your DB handling things in there a 
bit nicer than with just bash scripting.


Why PHP?
========

You may wonder: "Why use PHP for deployment?".
We wondered also why to use something else such as Ruby or Python.
PHP is one of the easiest languages to learn in my opinion and is
powerful enough, with its OO features, that one can create quite
extensive deployment mechanisms. Calling shell commands is also
trivial with PHP with e.g. 

    `ls -la`;

one can list the contents of the current folder through the shell.

PHP doesn't have difficult to understand constructs such as

    if [ "$variable" -eq "Hello" ]
    then
      echo "do something"
    fi
    
for simple ifs. It is much easier to remember the syntax as it is
very similar to C, Java and JavaScript.

It is a scripting languages and can be developed quickly.

It is very popular and well-known.

I plan to use it for PHP applications and PHP seems to be a sensible
choice for that.
