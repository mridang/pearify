<?php
/**
 *  Copyright (c) 2017 [Mridang Agarwalla]
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 */

namespace Pearify\Utils;

use Pearify\Logger;

class ComposerUtils
{

    /**
     * Returns the list of packages and all the other referenced packages from the composer file.
     *
     * @return array the array of referenced packages
     */
    public static function getDirectories() {
        Logger::info("Resolving dependencies for project");
        $packages = array();
        $root = dirname(dirname(dirname(dirname(__FILE__))));
        $composer = file_get_contents($root . DIRECTORY_SEPARATOR . 'composer.json');
        $composer = json_decode($composer, true);
        if (array_key_exists('require', $composer) && !empty($composer['require'])) {
            foreach ($composer['require'] as $requirement => $version) {
                $packages = array_merge(self::readFile($requirement), $packages);
            }
        } else {
            Logger::trace("Project has no dependencies");
        }
        foreach ($packages as $package) {
            Logger::debug("Project references package %s", $package);
        }
        Logger::info("%d dependencies found", count($packages));
        return $packages;
    }

    /**
     * Recursively reads a package and all the child dependency packages and returns a list of
     * packages
     *
     * @param $package string the name of the package to check
     * @return array the array of child package names
     */
    public static function readFile($package)
    {
        $root = dirname(dirname(dirname(dirname(__FILE__))));
        $path = implode(DIRECTORY_SEPARATOR, array($root, 'vendor', $package, 'composer.json'));
        Logger::trace("Reading composer file %s", $path);
        if (file_exists($path)) {
            $composer = json_decode(file_get_contents($path), true);
            if (array_key_exists('require', $composer) && !empty($composer['require'])) {
                $directories = array($package);
                Logger::trace("Checking dependencies for package %s", $package);
                foreach ($composer['require'] as $requirement => $version) {
                    $directories = array_merge(self::readFile($requirement), $directories);
                }
                return $directories;
            } else {
                Logger::trace("Package %s has no dependencies", $package);
                return array($package);
            }
        } else {
            return array();
        }
    }
}