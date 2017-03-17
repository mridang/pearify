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

use Pearify\File;
use Pearify\FoundClass;
use Pearify\TokenUtils;

class ClassFinder
{
    const IGNORED_KEYWORDS = ['parent', 'self', 'static'];
    private $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    public function find()
    {
        foreach ($this->file->tokens as $i => $t) {
            if (TokenUtils::isTokenType($t, array(T_NEW))) {
                // +2 to consume whitespace
                $c = self::findClassInNextTokens($this->file->tokens, $i + 2);
                if ($c) {
                    yield $c;
                }
            } elseif (TokenUtils::isTokenType($t, array(T_IMPLEMENTS, T_EXTENDS))) {
                // +2 to consume whitespace
                $c = self::findClassInNextTokens($this->file->tokens, $i + 2);
                if ($c) {
                    yield $c;
                }
                $j = $c->to + 1;
                while ($this->file->tokens[$j] == ",") {
                    // +2 to consume comma and whitepace
                    $c = self::findClassInNextTokens($this->file->tokens, $j + 2);
                    if ($c) {
                        yield $c;
                        $j = $c->to;
                    }
                    $j++;
                }

            } elseif (TokenUtils::isTokenType($t, array(T_PAAMAYIM_NEKUDOTAYIM, T_DOUBLE_COLON))) {
                $c = self::findClassInPreviousTokens($this->file->tokens, $i - 1);
                if ($c) {
                    yield $c;
                }
            } elseif (TokenUtils::isTokenType($t, array(T_CATCH))) {
                // +3 to consume brace and whitespace
                $c = self::findClassInNextTokens($this->file->tokens, $i + 3);
                if ($c) {
                    yield $c;
                }
            } elseif (TokenUtils::isTokenType($t, array(T_INSTANCEOF))) {
                $c = self::findClassInNextTokens($this->file->tokens, $i + 2);
                if ($c) {
                    yield $c;
                }
            } elseif (TokenUtils::isTokenType($t, array(T_FUNCTION))) {
                $j = $i + 4;
                $j = TokenUtils::isTokenType($this->file->tokens[$j], T_WHITESPACE) ? $j + 1 : $j;

                // +4 to consume whitespace, name, open brace
                $c = self::findClassInNextTokens($this->file->tokens, $j);
                if ($c) {
                    yield $c;
                    $j = $c->to;
                }

                while ($this->file->tokens[$j] != ")") {
                    if ($this->file->tokens[$j] == ',') {
                        // +2 to consume comma and whitepace
                        $c = self::findClassInNextTokens($this->file->tokens, $j + 2);
                        if ($c) {
                            yield $c;
                            $j = $c->to;
                        }
                    }
                    $j++;
                }

            }
        }
    }

    public static function findClassInNextTokens($tokens, $i)
    {
        $classname = '';
        for ($j = $i; TokenUtils::isTokenType($tokens[$j],
            array(T_NS_SEPARATOR, T_STRING)); $j++) {
            $classname .= self::tokenStr($tokens[$j]);
        }
        if ($classname && !in_array($classname, self::IGNORED_KEYWORDS)) {
            return new FoundClass($classname, $i, $j - 1);
        }

        return null;
    }

    public static function findClassInPreviousTokens($tokens, $i)
    {
        $classname = '';
        for ($j = $i; TokenUtils::isTokenType($tokens[$j],
            array(T_NS_SEPARATOR, T_STRING)); $j--) {
            $classname = self::tokenStr($tokens[$j]) . $classname;
        }
        if ($classname && !in_array($classname, self::IGNORED_KEYWORDS)) {
            return new FoundClass($classname, $j + 1, $i);
        }

        return null;
    }

    /**
     * Returns the string representation of a token or the string if it was one
     *
     * @param $token array|string the token to be stringified
     * @return string the stringified token
     */
    private static function tokenStr($token)
    {
        return is_string($token) ? $token : $token[1];
    }
}