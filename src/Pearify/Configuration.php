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

class Configuration
{
    public $replacements;

    /**
     * Initializes the configuration by reading the configuratio JSON file and builds an array
     * of replacement patterns.
     */
    public function __construct()
    {
        if (file_exists('pearify.json')) {
            $string = file_get_contents('pearify.json');
            $json = json_decode($string, true);
            if (array_key_exists('replacements', $json)) {
                Logger::info("Found replacement patterns for rewriting classes");
                if (!is_array($json['replacements'])) {
                    Logger::warn("Replacement patterns are misconfigured");
                }
                foreach ($json['replacements'] as $replacement) {
                    if (!array_key_exists('match', $replacement)
                        || !array_key_exists('replace', $replacement)
                    ) {
                        Logger::warn("Misconfigured replacement pattern found");
                    }
                    $this->replacements[] = $replacement;
                }
            }
        }
    }

    public function replace($string)
    {
        foreach ($this->replacements as $replacement) {
            $string = preg_replace($replacement['match'], $replacement['replace'], $string);
        }
        return $string;
    }
}
