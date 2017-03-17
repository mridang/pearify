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

namespace Pearify\Pearify;

use Pearify\Classname;
use Pearify\File;
use Pearify\Logger;
use Pearify\Utils\ComposerUtils;
use Symfony\Component\Console\Output\OutputInterface;

class Pearify
{
    private $paths;
    private $temp_dir;
    private $output;

    /**
     * Constructor for initializing the Pearify processor that initializes the
     * temporary directory and validates that the paths exist.
     *
     * @param array $paths the paths of files and directories to be processed
     * @param OutputInterface $output The output interface for logging messages
     */
    public function __construct($paths, OutputInterface $output)
    {
        Logger::init($output);
        $this->paths = $paths;
        $this->output = $output;
        $this->temp_dir = self::getTempDir();
    }

    public function process()
    {
        Logger::debug("Using %s as the temporary directory", $this->temp_dir);
        $packages = ComposerUtils::getDirectories();
        $vendors = implode(DIRECTORY_SEPARATOR, array(dirname(dirname(dirname(__FILE__))), 'vendor'));
        $vendir = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
        $parts = array($vendir, "composer", "autoload_classmap.php");
        /** @noinspection PhpIncludeInspection */
        $files = include implode(DIRECTORY_SEPARATOR, $parts);

        $map = array();
        foreach ($files as $class => $path) {
            foreach ($packages as $package) {
                if (0 === strpos($path, implode(DIRECTORY_SEPARATOR, array($vendir, $package)))) {
                    Logger::debug("Mapping file %s", $path);
                    $file = new File(file_get_contents($path));
                    $namespace = $file->getNamespace();
                    $name = $file->getFullClassname();

                    if (!array_key_exists($namespace, $map)) {
                        $map[$namespace] = array();
                    }

                    $map[$namespace][] = new Classname($name);
                    Logger::info("Found class %s in namespace %s", array($name, $namespace));
                } else {
                    Logger::trace("File %s not in list of allowed packages", array($path));
                }
            }
        }

        foreach ($files as $class => $path) {
            foreach ($packages as $package) {
                if (0 === strpos($path, implode(DIRECTORY_SEPARATOR, array($vendir, $package)))) {
                    echo $path . PHP_EOL;
                    Logger::info("Processing file %s", $path);
                    $f = new File(file_get_contents($path));
                    Logger::info("Renamed %s to %s", array($f->getFullClassname(), $f->getMagentfiedClassname()));
                    $f->setClassname($f->getMagentfiedClassname());
                    $f->findAndShortenClasses($map[$f->getNamespace()]);
                    $f->removeUses();
                    $f->removeNamespace();
                    //TODO: Whitespaces could be removed here
                    $f->save('./lib');
                }
            }

        }
    }

    /**
     * Returns a newly created temporary directory in the OS's temporary location.
     * All the files and folders of the package are moved to this directory and
     * packaged.
     *
     * @return string the path the newly created temporary directory
     */
    protected static function getTempDir()
    {
        $name = tempnam(sys_get_temp_dir(), 'tmp');
        unlink($name);
        mkdir($name, 0777, true);
        return $name;
    }
}
