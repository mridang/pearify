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

namespace Pearify;

use Symfony\Component\Console\Output\OutputInterface;

class Logger
{
    /** @var  OutputInterface */
    private static $output;

    public static function init(OutputInterface $output)
    {
        Logger::$output = $output;
    }

    /**
     * Prints a debug log message to the console with the trace message colour
     *
     * @param $message string the log message
     * @param array $args the array of format parameters
     */
    public static function trace($message, $args = array())
    {
        if (Logger::$output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            /** @noinspection HtmlUnknownTag */
            Logger::$output->writeln("<fg=cyan>" . vsprintf($message, $args));
        }
    }

    /**
     * Prints a debug log message to the console with the debug message colour
     *
     * @param $message string the log message
     * @param array $args the array of format parameters
     */
    public static function debug($message, $args = array())
    {
        if (Logger::$output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            /** @noinspection HtmlUnknownTag */
            Logger::$output->writeln('<fg=green>' . vsprintf($message, $args));
        }
    }

    /**
     * Prints an info log message to the console with the info message colour
     *
     * @param $message string the log message
     * @param array $args the array of format parameters
     */
    public static function info($message, $args = array())
    {
        /** @noinspection HtmlUnknownTag */
        Logger::$output->writeln('<fg=white>' . vsprintf($message, $args));
    }
}