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
    $config = require("$wd/packager/config.php");
    $myArgs = array();
    foreach ($config as $k => $v) {
        if ($k == 'files') {
            continue;
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
    $ret = array(
        'files' => $config['files'],
        'args' => $myArgs,
    );
    d($ret);
    return $ret;
}
function fpm() {
    global $wd;
    $version = incVersion($wd .'/version.txt');
    gitTag($version);
    $myArgs = parseConfig();

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
    x("fpm ". implode(" \\\n", $myArgs['args']) ." \\\n-v $version \\\n". $myArgs['files']);
    x("mv $wd/*.deb $wd/packager/deb/");
}
$wd = trim(`pwd`);
fpm();
