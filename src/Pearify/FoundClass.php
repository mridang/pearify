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

class FoundClass
{
    public $from;
    public $to;
    public $name;

    /**
     * Creates a new found class object
     *
     * @param string $name the name of the found class in the tokens
     * @param int $from the position from where the class token is
     * @param int $to the position till where the class token is
     */
    public function __construct($name, $from, $to)
    {
        $this->name = $name;
        $this->from = $from;
        $this->to = $to;
    }
}
