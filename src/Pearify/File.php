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

use Exception;
use Pearify\Ops\DocblockFixer;
use Pearify\Utils\ClassFinder;

class File
{
    public $tokens;
    public $originalUse;
    private $originalNamespace;
    private $lastClassKey;

    public function __construct($src)
    {
        $this->tokens = token_get_all($src);
        $this->originalUse = UseAs::createFromSrc($src);
        $this->originalClassname = $this->getClass();
        $this->ignoredClassNames = $this->getDeclaredClasses();
        $this->originalUse->addDisallowedAlias($this->originalClassname);
    }

    /**
     * Removes the namespace statement (if one is found) from the array of tokens.
     *
     * Namespaces aren't required as such by Magento's coding conventions and per the classname
     * rules in Magento, the classnames are pseudo fully qualified classnames where the slashes are
     * replaced with underscores, thereby making namespace statements useless
     */
    public function removeNamespace()
    {
        list($start, $end) = TokenUtils::positionForSequence([
            [T_NAMESPACE, 1],
            [[T_WHITESPACE, T_NS_SEPARATOR, T_STRING], '*'],
            [';', 1],
            [T_WHITESPACE, '*'],
        ], $this->tokens);

        if ($start && $end) {
            Logger::debug("Removing namespace statement from file");

            array_splice($this->tokens, $start, $end - $start);
        } else {
            Logger::trace("No namespace statement to be removed from the file");
        }
    }

    /**
     * Removes the use-ad statements (if any are found) from the array of tokens.
     *
     * Namespaces are not required as such by Magento's coding conventions and per the classname
     * rules in Magento and all classnames are pseudo fully qualified classnames where the slashes
     * are replaced with underscores so use statements aren't required as such.
     */
    public function removeUses()
    {
        $positions = TokenUtils::allPositionsForSequence([
            [T_USE, 1],
            [[T_WHITESPACE, T_NS_SEPARATOR, T_STRING, T_AS], '*'],
            [';', 1],
            [T_WHITESPACE, '*'],
        ], $this->tokens);

        if ($positions) {
            Logger::trace("Removing use statements from file");
            $offset = $positions[0][0];
            if (!empty($offset) && $this->hasTraitsUse()) {
                // Calculate distance to class declaration and subtract here
                foreach ($positions as $key => $position) {
                    if ($position[0] > $this->lastClassKey) {
                        unset($positions[$key]);
                    }
                }
            }
            $length = $positions[count($positions) - 1][1] - $offset + 1;

            array_splice($this->tokens, $offset, $length);
        } else {
            Logger::trace("No use statements to be removed from the file");
        }
    }

    /**
     * Check if file has use statement after class declaration.
     * Also set class variable with latest class declaration token position
     *
     * @return bool
     */
    private function hasTraitsUse()
    {
        $this->lastClassKey = null; // Clear to avoid old values
        foreach ($this->tokens as $key => $token) {
            if (empty($token[1]) || $token[1] !== 'class') {
                return false;
            }
            $lineNumber = $token[2];
            // If the previous token is in a line before
            // means class keyword is not a class declaration statement
            if (empty($this->tokens[$key - 1])
                || $this->tokens[$key - 1][2] === $lineNumber
            ) {
                return false;
            }
            // Check for use statements from the class declaration onwards
            for ($i = $key, $max = count($this->tokens); $i < $max; $i++) {
                if(!is_array($this->tokens[$i])) {
                    // We may have elements only with ';', so skip it
                    continue;
                }
                // If has a use statement after class declaration and this is
                // the first statement of the line, we have traits in the class
                if ($this->tokens[$i][1] === 'use'
                    && !empty($this->tokens[$i - 1][2])
                    && $i[2] !== $this->tokens[$i - 1][2]
                ) {
                    // $key is where the class statement is located
                    $this->lastClassKey = $key;
                    return true;
                }
                // Break if find public/protected/private or constants for better performance
                if ($this->tokens[$i][1] === 'public'
                    || $this->tokens[$i][1] === 'private'
                    || $this->tokens[$i][1] === 'protected'
                    || $this->tokens[$i][1] === 'const'
                ) {
                    return false;
                }
            }
        }
        return false;
    }

    public function findAndShortenClasses($map, Configuration $config)
    {
        $finder = new ClassFinder($this);
        $classesToFix = iterator_to_array($finder->find());
        Logger::debug("Shortening %d classes", count($classesToFix));
        $this->shortenClasses($classesToFix, $config);
        $this->tokens = DocblockFixer::fix($this, $map, $config)->tokens;
    }

    public function getClass()
    {
        $cc = $this->getDeclaredClasses();
        $c = array_shift($cc);
        return $c;
    }

    public function getDeclaredClasses()
    {
        $classes = [];
        $pos = 0;
        do {
            list($first, $last) = TokenUtils::positionForSequence(
                [
                    [[T_CLASS, T_INTERFACE, T_TRAIT], 1],
                    [T_WHITESPACE, '*']
                ], $this->tokens, $pos
            );

            if ($last) {
                $classes[] = ClassFinder::findClassInNextTokens($this->tokens, $last + 1)->name;
                $pos = $last;
            }
        } while ($first && $last);

        return $classes;
    }

    public function setClassname($classname)
    {
        $this->originalUse->addDisallowedAlias($classname);

        list($first, $last) = TokenUtils::positionForSequence([
            [[T_CLASS, T_INTERFACE, T_TRAIT], 1],
            [T_WHITESPACE, '*'],
            [T_STRING, '1'],
        ], $this->tokens);

        if ($last) {
            $t = token_get_all($classname);
            $t[0][0] = T_STRING;
            array_splice($this->tokens, $last, 1, $t);
        } else {
            throw new Exception("no class found");
        }
    }

    /**
     * Returns the Pearified classname of the class built by replacing all the slashes with
     * underscores in the fully qualified classname
     *
     * @return string the Pearified classname
     */
    public function getMagentfiedClassname()
    {
        return trim(str_replace('\\', '_', $this->getFullClassname()), '_');
    }

    /**
     * Returns the fully qualified classname of the class by merging the classname with the
     * namespace
     *
     * @return Classname the fully qualified classname of the class
     */
    public function getFullClassname()
    {
        $ns = $this->getNamespace();
        $class = $this->getClass();
        return $ns ? new Classname("\\$ns\\$class") : new Classname($class);
    }

    public function getOriginalNamespace()
    {
        return $this->originalNamespace ?: $this->getNamespace();
    }

    public function getNamespace()
    {

        list($null, $pos) = TokenUtils::positionForSequence([
            [T_NAMESPACE, 1],
            [T_WHITESPACE, '*'],
        ], $this->tokens);

        if ($pos) {
            if (!ClassFinder::findClassInNextTokens($this->tokens, $pos + 1)) {
                throw new Exception();
            }
            return ClassFinder::findClassInNextTokens($this->tokens, $pos + 1)->name;
        }

        return '';
    }

    /**
     * Resolves a class to it's fully-qualified using a few different mechanisms. If the class
     * begins with a slash, we assume that it is already qualified. If the class exists in the list
     * of use-statements, then we resolve the fully qualified name using the uses. Lastly, if the
     * class doesn't begin with a slash or exist as a use-statement, we resolve the name using the
     * namespace of the current class.
     *
     * @param $found FoundClass the found class to resolve
     * @return bool|Classname
     */
    public function resolveClass(FoundClass $found)
    {
        Logger::trace("Resolving class %s", $found->name);
        if ($found->name[0] === '\\') {
            return new Classname($found->name);
        }

        $classParts = explode('\\', $found->name);
        if (count($classParts) >= 2) {
            $b = array_shift($classParts);
            $baseClass = $this->originalUse->get($b);
            if ($baseClass) {
                return new Classname($baseClass . '\\' . implode('\\', $classParts));
            }
        }
        if ($c = $this->originalUse->get($found->name)) {
            Logger::trace("Found a use statement for class");
            Logger::trace("Resolved to %s", $c);
            return $c;
        } else {
            $class = Classname::build($this->getOriginalNamespace(), $found->name);
            Logger::trace("Resolved to %s", $class->classname);
            return $class;
        }
    }

    /**
     * fixClasses replaces all classnames with the shortest version of a class name possible
     *
     * @param $classesToFix
     * @param $config
     */
    public function shortenClasses($classesToFix, Configuration $config)
    {
        $cumulativeOffset = 0;

        /** @var FoundClass $c */
        foreach ($classesToFix as $c) {
            $replacement = [];

            if (in_array($c->name, $this->ignoredClassNames)) {
                if ($c->name == $this->originalClassname) {
                    $replacement = [[T_STATIC, "static", 2]];
                } else {
                    continue;
                }
            }

            if (!$replacement) {
                $resolvedClass = $this->resolveClass($c);
                $alias = $config->replace($this->originalUse->getAliasForClassname($resolvedClass));
                Logger::trace("Resolved class %s to %s", array($resolvedClass->classname, $alias));
                $replacement = array(array(308, $alias, 2));
            }

            $offset = $c->from;
            $length = $c->to - $c->from + 1;

            array_splice($this->tokens, $offset + $cumulativeOffset, $length, $replacement);

            $cumulativeOffset -= $length - 1;
        }
    }

    /**
     * Returns the rebuilt source code by processing each token back as it was
     *
     * @return string the rebuilt source code
     */
    public function getSrc()
    {
        $content = "";
        foreach ($this->tokens as $token) {
            $content .= is_string($token) ? $token : $token[1];;
        }

        return $content;
    }

    /**
     * Saves the file to the location complying with the Magento classloader's file placement
     * structure
     *
     * @param string $directory the directory to which to save the file
     */
    public function save($directory)
    {
        $parts = explode('_', $this->getClass());
        array_unshift($parts, $directory);
        $path = join(DIRECTORY_SEPARATOR, $parts) . '.php';
        self::saveToFile($path, $this->getSrc());
    }

    /**
     * Extended version of file put contents that also creates the directory tree if it doesn't
     * exist.
     *
     * @param string $path the absolute or relative path to which to save the data
     * @param string $contents the contents to be saved to the file
     */
    private static function saveToFile($path, $contents)
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        file_put_contents($path, $contents);
    }
}
