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

class UseAs
{
    public $classnames = array();
    private $disallowed = array(
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor'
    );
    private $conflictCtr = 1;

    public static function createFromSrc($src)
    {
        $useas = new static();
        preg_match_all('/\nuse ([\w\\\\]+)( as ([\w\\\\]+))?;/', $src, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $class = new Classname($m[1]);
            $useas->addClassname(
                $class,
                isset($m[3]) ? $m[3] : $class->nameWithoutNamespace()
            );
        }

        return $useas;
    }

    public function addDisallowedAlias($alias)
    {
        $this->disallowed[] = strtolower($alias);
    }

    public function isAliasValid($alias)
    {
        return !in_array(strtolower($alias), $this->disallowed);
    }

    public function addClassname(Classname $class, $alias = null)
    {
        // echo "adding ";
        // var_dump($class);
        if (!$alias) {
            $alias = $class->short();
            if ($this->has($alias) || !$this->isAliasValid($alias)) {
                $alias = $class->nameWithoutNamespace();
                if ($this->has($alias) || !$this->isAliasValid($alias)) {
                    $alias = str_replace('\\', '_', $class->nameWithNamespace());
                }
            }
        }
        if ($this->has($alias) || !$this->isAliasValid($alias)) {
            $alias .= $this->conflictCtr++;
        }

        $this->classnames[] = [
            'classname' => $class,
            'alias' => $alias,
        ];

        return $alias;
    }

    /**
     * Renamed the given class name by replacing all slashes with underscores for loading using
     * Magento's classloader
     *
     * @param Classname $class the class name object to be renamed
     * @return string the Magento autoloader-compliant class name
     */
    public function getAliasForClassname(Classname $class)
    {
        foreach ($this->classnames as $c) {
            if ($c['classname']->equals($class)) {
                return str_replace('\\', '_', ltrim($class->classname, '\\'));
            }
        }

        return str_replace('\\', '_', ltrim($class->classname, '\\'));
    }

    public function hasClassname(Classname $class)
    {
        foreach ($this->classnames as $c) {
            if ($c['classname']->equals($class)) {
                return true;
            }
        }

        return false;
    }

    public function has($classAlias)
    {
        foreach ($this->classnames as $c) {
            if (strtolower($c['alias']) == strtolower($classAlias)) {
                return true;
            }
        }

        return false;
    }

    public function get($classAlias)
    {
        foreach ($this->classnames as $c) {
            if ($c['alias'] == $classAlias) {
                return $c['classname'];
            }
        }

        return false;
    }

    public function getTokens($namespace)
    {
        $ignoreNonCompound = !$namespace;
        if (!$this->classnames) {
            return [];
        }

        $phpSrc = "<?php\n";
        $suffix = '';
        foreach ($this->classnames as $c) {
            $hasAlias = $c['alias'] != $c['classname']->nameWithoutNamespace();
            if ($ignoreNonCompound && !$hasAlias && !strpos($c['classname'], '\\')) {
                continue;
            }
            if (!$hasAlias && '\\' . $namespace == $c['classname']->ns()) {
                continue;
            }

            $phpSrc .= 'use ' . $c['classname']->nameWithNamespace();
            if ($hasAlias) {
                $phpSrc .= " as " . $c['alias'];
            }
            $phpSrc .= ";\n";
            $suffix = "\n";
        }
        $t = token_get_all($phpSrc . $suffix);

        return array_slice($t, 1);
    }

    public function replaceClasses($map)
    {
        foreach ($this->classnames as &$c) {
            $classkey = (string)$c['classname'];
            if (isset($map[$classkey])) {
                $c['classname'] = new Classname($map[$classkey]);
            }
        }
    }
}
