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

namespace Pearify\Ops;

use Pearify\Classname;
use Pearify\File;
use Pearify\TokenUtils;

/**
 * Doc-block fixer class that iterates all the doc-blocks in a file and replaces all valid class
 * references (from use-as statements and other explicitly specified classes).
 *
 * @package Pearify\Ops
 */
class DocblockFixer
{

    /**
     * Private constructor to prevent instantiation of utilities and helper classes and objects.
     */
    private function __construct() {}

    /**
     * Replaces all the class references in all the doc-block comments in a file. Only the following
     * class references are replaced:
     * 1. All class references must begin with one or more empty spaces or a pipe. Having the
     * leading space allows us to assume that the classname is either part of a sentence or part of
     * and @<something> documentation directive. The pipe operator allows us to assume that the
     * classname chained to another class reference.
     * 2. All class references must end with one or more spaces, a pipe or a square bracket. The
     * reasons for the space and pipe align with the reasons mentioned above, while the existence of
     * a square bracket allows us to assume that this classname is part of a array of objects.
     *
     * @param File $file the file object whose doc-blocks should be parsed and corrected
     * @param $map Classname[] an additional array of classnames to be searched for
     * @return File the modified file object with all the doc-blocks checked and corrected
     */
    public static function fix(File $file, $map)
    {
        foreach ($file->originalUse->classnames as $use) {
            $map[] = $use['classname'];
        }

        // Iterate each token in the file and check if is a doc-block token. If it is, then run
        // through all the replacements and replace all the references.
        foreach ($file->tokens as $id => $token) {
            if (!is_string($token) && TokenUtils::isTokenType($token, T_DOC_COMMENT)) {
                /** @var Classname $class */
                foreach ($map as $class) {
                    $token[1] = self::replace($class->nameWithNamespace(), $class, $token);
                    $token[1] = self::replace($class->nameWithoutNamespace(), $class, $token);
                }
                $file->tokens[$id] = $token;
            }
        }

        return $file;
    }

    private static function replace($oldName, Classname $className, $docBlock) {
        $search = '/([\s|]+)(' . str_replace('\\', '\\\\', '[\\]?' . $oldName) . ')([\s|\[])/';
        $replacement = '${1}' . $className->pearifiedName() . '${3}';
        return preg_replace($search, $replacement, $docBlock[1]);
    }
}