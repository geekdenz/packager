#!/usr/bin/php
<?php
$debug = false;
include 'Colors.php';
$colors = new Colors();
function e($str, $color = 'white', $bg_color = 'black') {
    global $colors;
    echo $colors->getColoredString($str ."\n", $color, $bg_color);
}
function x($command, $color = 'cyan', $bg_color = 'black') {
    global $debug,$colors;
    echo $colors->getColoredString($command, $color, $bg_color) ."\n";
    if (!$debug) {
        passthru($command);
    }
}
function d($v) {
    global $debug;
    if ($debug) {
        print_r($v);
        echo "\n";
    }
}
function incVersion($filename) {
    // get current version from version.txt
    if ($filename[0] != '/') {
        $filename = dirname(__FILE__) .'/'. $filename;
    }
    if (file_exists($filename)) {
        $versionString = file_get_contents($filename);
    } else {
        $versionString = '0.1.0';
    }
    $parts = explode('.', $versionString);
    $major = (int) $parts[0];
    $minor = (int) $parts[1];
    $rev = (int) $parts[2];
    // increment version number
    $rev++;
    $newVersion = "$major.$minor.$rev";
    file_put_contents($filename, $newVersion);
    return $newVersion;
}
function gitTag($version) {
    // git tag -a v<version> -m "version <version>"
    // git tag -a v0.2 -m "version 0.2"
    $cmd = "git tag -a v$version -m 'version $version'";
    x($cmd);
}
function parseConfig() {
    global $config,$wd;
    $configs = require("$wd/packager/config.php");
    $rets = array();
    foreach ($configs as $name => $config) {
        $myArgs = array();
        $user = trim(`whoami`);
        $target_dir = '';
        $before_package = false;
        foreach ($config as $k => $v) {
            if ($k == 'files' || $k == 'repository' 
                    || $k == 'user' || $k == 'include_folder'
                    || $k == 'target_dir' || $k == 'before-package') {
                if ($k == 'user') {
                    $user = $v;
                } elseif ($k == 'before-package') {
                    $before_package = $v; // path to php script to run before package
                }
                continue;
            }
            if ($k == 'C') {
                $target_dir = $v;
            }
            $argk = "-";
            if (strlen($k) > 1) {
                $argk .= "-";
            }
            $argk .= $k;
            if (is_array($v)) {
                $argValue = "";
                foreach ($v as $a) {
                    if (strlen($argValue) > 0) {
                        $argValue .= ' ';
                    }
                    $argValue .= $argk ." '". $a ."'";
                }
            } else {
                $argValue = "$argk '$v'";
            }
            $myArgs[] = $argValue;
        }
        $rets[$name] = array(
            'target_dir' => $target_dir,
            'repository' => $config['repository'],
            //'prefix' => $config['prefix'],
            'files' => $config['files'],
            'args' => $myArgs,
            'user' => $user,
            'before_package' => $before_package,
        );
    }
    d($rets);
    return $rets;
}
function getIncludeDir() {
    return 'deb_install_'. sha1(time());
}
function generateBashScript($dir, $target_dir, $name, $prefix, $script_dir, $packager_root) {
    $actions = array(
        'after-install',
        'before-install',
        'after-remove',
        'before-remove',
    );
    $actions_todo = array();
    $script  = "#!/bin/bash\n";
    $num_dotdot = count(explode('/', $prefix)); // str_repeat('../', $num_dotdot) .
    foreach ($actions as $action) {
        $phpfile = "$dir/$action.php";
        //echo "exists? packager/include_dir/$phpfile\n";
        if (file_exists("packager/$action.php")) {
            echo "Adding action: $action ...\n";
            x("cp packager/$action.php $packager_root$script_dir");
            $script  = "#!/bin/bash\n";
            $script .= "/usr/bin/php $script_dir/$action.php\n";
            file_put_contents("packager/$action.bash", $script);
            $actions_todo[] = $action;
        }
    }
    $ar = 'packager/after-remove.bash';
    $rm_command = "\n\nrm -rf $script_dir";
    if (file_exists($ar)) {
        $handle = fopen($ar, 'a');
        fwrite($handle, $rm_command);
    } else {
        file_put_contents($ar, "#!/bin/bash". $rm_command);
    }

    return $actions_todo;
}
function cleanup() {
    x("rm -rf packager/root");
    x("rm -rf packager/*.bash");
}
function main() {
    global $wd;
    $version = incVersion($wd .'/version.txt');
    gitTag($version);
    $packages = parseConfig();

    /*
    x("fpm -d tomcat7 \
        --before-install $wd/packager/beforeinstall.php \
        --after-remove $wd/packager/uninstall.bash \
        -s dir -t deb -m heuert@landcareresearch.co.nz \
        --url http://confluence.landcareresearch.co.nz/display/IFX/Azimuth+and+Geolocate+Images+Tool+Requirements \
        --description 'The Geolocate Images Tool will be a web based tool that will let users upload a collection of geo-located photos to be displayed on a map.' \
        --license 'GPL v3' --vendor 'Landcare Research' \
        -v $version \
        -n 'landcare-phototool' \
        -C target/ --prefix=/var/lib/tomcat7/webapps $wd/target/landcare-azimuth-map-1.0-SNAPSHOT.war");
     */
    foreach ($packages as $name => $package) {
        $files = $package['files'];
        if (!is_array($files)) {
            //$files = array($files);
            echo "files must be an array!\n";
            return false;
        }
        $include_dir = $name; //getIncludeDir();
        $trigger_files_dir = 'packager/include_dir';
        $target_dir = $package['target_dir'];
        $packager_root = "packager/root";
        foreach ($files as $k => $file) {
            $files[$k] = substr($file, 1);
        }
        //if (file_exists($trigger_files_dir)) {
            $script_dir = "/var/cache/deb/$name"; // note that this will be from '/' (root)
            $generated_dir = "$target_dir/$include_dir";
            x("mkdir -p $packager_root$script_dir");
            $files = array_merge($files, array(substr($script_dir, 1)));
            $prefix = '/';
            $actions = generateBashScript($include_dir, $target_dir, $name, $prefix, $script_dir, $packager_root);
        //}
        $package_args = implode(" \\\n", $package['args']);
        $afterinstall = "";
        if (!in_array('after-remove', $actions)) {
            $actions[] = 'after-remove';
        }
        foreach ($actions as $action) {
            $package_args .= " --$action packager/$action.bash \\\n";
        }
        foreach ($files as $kf => $file) {
            x("mkdir -p $packager_root/". dirname($file));
            if ($kf) {
                x("cp $kf $packager_root/$file");
            }
        }
        $files = implode(' ', $files);
        x("fpm -C packager/root --prefix / -n $name $package_args \\\n-v $version \\\n$files");
        x("mv $wd/*.deb $wd/packager/deb/");
        x("scp packager/deb/". $name ."_${version}_*.deb ". $package['user'] .'@'. $package['repository']);
        cleanup();
    }
}
$wd = trim(`pwd`);
main();
