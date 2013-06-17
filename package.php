#!/usr/bin/php
<?php
$debug = false;
include 'Colors.php';
$colors = new Colors();

$dotag = false; // tag code with version, for release
$doinc = false; // increment version number
$dorelease = false; // upload to repository
$docleanup = false; // cleanup after packaging
$doactions = array(
    'after-install' => true,
    'before-install' => true,
    'after-remove' => true,
    'before-remove' => true,
    'before-package' => true,
    'after-package' => true,
);

foreach ($argv as $k => $arg) {
    if ($arg[0] != '-') {
        continue;
    }
    if ($arg == '--help') {
        printUsage();
        die();
    }
    if ($arg[0] == '-' && $arg[1] == '-') {
        printUsage();
        die();
    } else if ($arg[0] == '-') {
        for ($i = 0, $leni = strlen($arg); ++$i < $leni;) {
            switch ($arg[$i]) {
                default:
                case '-':
                    break; //TODO
                case 'g':
                    $dotag = true;
                    $doinc = true;
                    break;
                case 'i':
                    $doinc = true;
                    break;
                case 't':
                    $dotag = true;
                    break;
                case 'r':
                    $dorelease = true;
                    break;
                case 'c':
                    $docleanup = true;
                    break;
                case 'd':
                    $action = $argv[$k+1];
                    $doactions[$action] = false;
                    break;
            }
        }
    }
}
function dbool($booleanValue) {
    global $debug;
    if (!$debug) return;
    echo "" . ($booleanValue ? "true\n" : "false\n");
}

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
    global $debug;
    // git tag -a v<version> -m "version <version>"
    // git tag -a v0.2 -m "version 0.2"
    if (!$debug) {
        x("git commit -am 'automatic commit: package.php version v$version'");
        $cmd = "git tag -a v$version -m 'version $version'";
        x($cmd);
    }
}
function parseConfig() {
    global $config,$wd,$doactions;
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
                } elseif ($k == 'before-package' && $doactions['before-package']) {
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
    global $doactions;
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
        if (!$doactions[$action]) {
            continue;
        }
        //$phpfile = "$dir/$action.php";
        //echo "exists? packager/include_dir/$phpfile\n";
        if (file_exists("packager/$name/$action.php")) {
            echo "Adding action: $action ...\n";
            x("cp packager/$name/$action.php $packager_root$script_dir");
            $script  = "#!/bin/bash\n";
            $script .= "/usr/bin/php $script_dir/$action.php\n";
            file_put_contents("packager/$name/$action.bash", $script);
            $actions_todo[] = $action;
        }
    }
    $ar_dir = 'packager/'. $name;
    $is_dir = is_dir($ar_dir);
    $is_file = file_exists($ar_dir);
    if ($is_file && !$is_dir) {
        die("$ar_dir must be a directory");
    } elseif (!$is_file) {
        x("mkdir -p $ar_dir");
    }
    $ar = $ar_dir .'/after-remove.bash';
    $rm_command = "\n\nrm -rf $script_dir";
    if (!file_exists($ar)) {
        file_put_contents($ar, "#!/bin/bash". $rm_command);
    }

    return $actions_todo;
}
function init() {
    x("mkdir -p packager/deb");
}
function cleanup() {
    x("rm -rf packager/root");
    x("rm -rf packager/*.bash");
}
function checkFilesThere() {
    global $wd;
    $requirements = array(
        'packager',
        'packager/config.php',
    );
    foreach ($requirements as $req) {
        if (!file_exists($req)) {
            return false;
        }
    }
    return true;
}
function printUsage() {
    echo file_get_contents(dirname(__FILE__) .'/USAGE.md') ."\n";
}
function main() {
    global $wd,$debug,$dotag,$doinc,$dorelease,$docleanup,$doactions;
    if (!checkFilesThere()) {
        printUsage();
        die();
    }
    if ($doinc) {
        $version = incVersion($wd .'/version.txt');
    } else {
        $version = file_get_contents($wd.'/version.txt');
    }
    if ($dotag) {
        gitTag($version);
    }
    $packages = parseConfig();
    init();

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

        $script_dir = "/var/cache/deb/$name"; // note that this will be from '/' (root)
        $generated_dir = "$target_dir/$include_dir";
        x("mkdir -p $packager_root$script_dir");
        $files = array_merge($files, array(substr($script_dir, 1)));
        $prefix = '/';
        $actions = generateBashScript($include_dir, $target_dir, $name, $prefix, $script_dir, $packager_root);

        $package_args = implode(" \\\n", $package['args']);
        if (!in_array('after-remove', $actions)) {
            $actions[] = 'after-remove';
        }
        foreach ($actions as $action) {
            $package_args .= " --$action packager/$name/$action.bash \\\n";
        }
        foreach ($files as $kf => $file) {
            x("mkdir -p $packager_root/". dirname($file));
            if (is_string($kf)) { // && $kf != 'packager') {}
                $ends_with_slash = substr($file, -1) == '/';
                if (is_dir($kf) || $ends_with_slash) {
                    x("mkdir -p $packager_root/$file");
                } else {
                    x("mkdir -p $packager_root/". dirname($file));
                }
                $files_from = glob($kf);
                //d($files_from);
                $files_from = array_filter($files_from, create_function('$a', 'return $a != "packager";'));
                d($files_from);
                $sources = implode(' ', $files_from);
                if ($ends_with_slash) {
                    x("cp -Rp $sources $packager_root/$file");
                } elseif (count($files_from) == 1) {
                    x("cp -Rp $kf $packager_root/$file"); 
                }
            }
        }
        $files = implode(' ', $files);
        $before_package = $package['before_package'];
        if ($before_package && !file_exists("packager/$before_package")) {
            e("Warning $before_package does not exist\n", 'red');
        } elseif ($before_package) {
            require_once("packager/$before_package");
        }
        x("fpm -C packager/root --prefix / -n $name $package_args \\\n-v $version .");
        x("mv $wd/*.deb $wd/packager/deb/");
        if ($dorelease) {
            x("scp packager/deb/". $name ."_${version}_*.deb ". $package['user'] .'@'. $package['repository']);
        }
        $after_package = "packager/$name/after-package.php";
        if ($doactions['after-package'] && file_exists($after_package) && !$debug) {
            require_once($after_package);//sudo puppet agent -t
        }
        if ($docleanup) {
            cleanup();
        }
    }
}
$wd = trim(`pwd`);
main();
