Usage
=====
1. create a 'packager' directory in your project
2. create a 'packager/config.php'
3. optionally create 'packager/after-install.php'
4. optionally create 'packager/before-remove.php'

Execute
    package.php
in the root directory of your project and a package will be created for you and uploaded to your repository.

The package can be found in packager/deb with version etc. Git tags are automatically created for you
and the version can be customized by editing 'version.txt'.

More information can be found @ https://github.com/geekdenz/packager or in README.md.

Command Line Arguments
======================

-g          turn on git tagging of version and version increment, equivalent to -it
-t          turn on git tagging
-i          turn on version increment
-d <action> delete action from the actions to do, i.e. before-package, after-package etc
-r          release, upload to repository given in config
