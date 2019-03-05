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

use ArrayIterator;
use ArrayObject;

class TokenUtils
{
    public static function allPositionsForSequence(array $tokenSequence, $tokens, $before = null)
    {
        $before = $before ?: PHP_INT_MAX;
        $positions = array();
        $lastpos = null;
        while ($position = self::sequenceMatch($tokenSequence, $tokens, $lastpos)) {
            if ($position[1] > $before) {
                break;
            }
            $positions[] = $position;
            $lastpos = $position[1]+1;
        }

        return $positions;
    }

    /**
     * Utility method to find a sequence of tokens matching the specified tokenisation pattern.
     *
     * @param array $sequence the tokenisation sequence pattern to match
     * @param array $tokens the array of tokens to match as returned by token_get_all
     * @param int $startFrom an optional starting index from which to begin searching
     * @return array the start and end position of the tokenisation sequence pattern
     */
    public static function positionForSequence(array $sequence, $tokens, $startFrom = null)
    {
        $seqIterator = (new ArrayObject($sequence))->getIterator();
        $tokenIterator = (new ArrayObject($tokens))->getIterator();
        if ($startFrom != null) {
            $tokenIterator->seek($startFrom);
        }
        while ($tokenIterator->valid()) {
            $seqIterator->rewind();
            $keys = array();
            list($allowedTokens, $timesAllowed) = $seqIterator->current();
            self::seekToNextType($tokenIterator, $allowedTokens);
            while ($tokenIterator->valid()) {
                if (!$seqIterator->valid()) {
                    $first = array_shift($keys);
                    $last = array_pop($keys);

                    return array($first, $last);
                }

                list($allowedTokens, $timesAllowed) = $seqIterator->current();
                if ($timesAllowed == '*') {
                    while ($tokenIterator->valid() && self::isTokenType($tokenIterator->current(),
                            $allowedTokens)) {
                        $keys[] = $tokenIterator->key();
                        $tokenIterator->next();
                    }
                } else {
                    for ($i = 0; $i < $timesAllowed; $i++) {
                        if (self::isTokenType($tokenIterator->current(), $allowedTokens)) {
                            $keys[] = $tokenIterator->key();
                            $tokenIterator->next();
                        } else {
                            continue 3;
                        }
                    }
                }
                $seqIterator->next();
            }
        }

        return;
    }

    /**
     * @param array $tokenSequence
     * @param $tokens
     * @param null $startFrom
     * @return array|void
     */
    public static function sequenceMatch(array $tokenSequence, $tokens, $startFrom = null)
    {
        $seqIterator = (new ArrayObject($tokenSequence))->getIterator();
        $tokenIterator = (new ArrayObject($tokens))->getIterator();
        if ($startFrom !== null) {
            $tokenIterator->seek($startFrom);
        }
        while ($tokenIterator->valid()) {
            $seqIterator->rewind();
            $keys = array();
            list($allowedTokens, $timesAllowed) = $seqIterator->current();
            self::seekToNextType($tokenIterator, $allowedTokens);
            while ($tokenIterator->valid()) {
                if (!$seqIterator->valid()) {
                    $first = array_shift($keys);
                    $last = array_pop($keys);

                    return array($first, $last);
                }

                list($allowedTokens, $timesAllowed) = $seqIterator->current();
                if ($timesAllowed == '*') {
                    while ($tokenIterator->valid() && self::isTokenType($tokenIterator->current(), $allowedTokens)) {
                        $keys[] = $tokenIterator->key();
                        $tokenIterator->next();
                    }
                } else {
                    for ($i = 0; $i < $timesAllowed; $i++) {
                        if (self::isTokenType($tokenIterator->current(), $allowedTokens)) {
                            $keys[] = $tokenIterator->key();
                            $tokenIterator->next();
                        } else {
                            continue 3;
                        }
                    }
                }
                $seqIterator->next();
            }
        }

        return;
    }

    /**
     * Seeks ahead using the iterator until the next token matching the type
     *
     * @param ArrayIterator $tokenIterator the token iterator to seek
     * @param array $type the type of the token seek to
     */
    private static function seekToNextType(ArrayIterator $tokenIterator, $type)
    {
        while ($tokenIterator->valid()) {
            if (self::isTokenType($tokenIterator->current(), $type)) {
                return;
            }
            $tokenIterator->next();
        }
    }

    /**
     * Checks whether a given token is any one of the specified token types
     *
     * @param array $token the token to check
     * @param array $types the array of types
     * @return bool true if the token matches one the types
     */
    public static function isTokenType($token, $types)
    {
        if (!is_array($types)) {
            $types = array($types);
        }
        return is_array($token) ? in_array($token[0], $types) : in_array($token, $types);
    }
}
