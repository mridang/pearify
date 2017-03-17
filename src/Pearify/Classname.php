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

class Classname {

    public $classname;

    public static function build($namespace, $classname) {
        return new Classname($namespace . '\\' . $classname);
    }

    public function __construct($classname) {
        if ($this->classname[0] != '\\') {
            $this->classname = '\\'.$classname;
        } else {
            $this->classname = $classname;
        }
    }

    public function nameWithNamespace() {
        return ltrim($this->classname, '\\');
    }

    public function nameWithNamespaceAndLeadingSlash() {
        return $this->classname;
    }

    public function nameWithoutNamespace() {
        $parts = explode('\\', $this->classname);
        return array_pop($parts);
    }

    public function pearifiedName() {
        return str_replace('\\', '_', $this->nameWithNamespace());
    }

    public function short() {
        $parts = explode('\\', $this->classname);
        $parts = explode('_', array_pop($parts));
        return array_pop($parts);
    }

    public function ns() {
        $parts = explode('\\', $this->classname);
        array_pop($parts);

        return implode('\\', $parts);
    }

    public function equals(Classname $c) {
        return $c->nameWithNamespace() == $this->nameWithNamespace();
    }

    public function __toString() {
        return $this->nameWithNamespace();
    }
}
