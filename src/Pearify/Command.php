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
namespace Pearify\Command;

use Pearify\Pearify\Pearify;
use Pearify\Utils\FileUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Params extends Command
{

    protected function configure()
    {
        $this
            ->setName('process')
            ->setDescription('Processes the classes to be Magento autoloader-compatible')
            ->addArgument(
                'paths',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'List of files or directories'
            );
    }

    /**
     * Main CLI method that validates that the specified path exist, is a file
     * and a well-formed JSON file else exits with status code 0.
     *
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $paths = $input->getArgument('paths');
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                self::error($output, "One or more of the specified paths are missing");
                return;
            }
        }

        if (!FileUtils::deleteDirectory('./lib')) {
            self::error($output, "Unable to remove the output directory");
        };

        if (!file_exists('./lib') || !is_dir('./lib')) {
            mkdir('./lib', 0755, true);
        }

        $magentofier = new Pearify($paths, $output);
        $magentofier->process();
    }

    /**
     * Prints an info log message to the console with the warning message colour
     *
     * @param $output
     * @param $message string the log message
     * @param array $args the array of format parameters
     */
    private function error(OutputInterface $output, $message, $args = array())
    {
        /** @noinspection HtmlUnknownTag */
        $output->writeln('<error>' . sprintf($message, $args) . '</error>');
        exit(0);
    }
}