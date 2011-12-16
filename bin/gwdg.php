#!/usr/bin/env php
<?php

/**
 * Documentation generator
 *
 * This scripts scans all files in the lib/ directory, and generates
 * Google Code wiki documentation.
 *
 * This script is rather crappy. It does what it needs to do, but uses global
 * variables and it might be a hard to read.
 *
 * I'm not sure if I care though. Maybe one day this can become a separate
 * project
 *
 * To run this script, just execute on the command line. The script assumes
 * it's in the standard bin/ directory.
 */
date_default_timezone_set('UTC');

$libDir = realpath(__DIR__ . '/../lib');
$outputDir = __DIR__ . '/../docs/wikidocs';

if (!is_dir($outputDir)) mkdir($outputDir);

$files = new RecursiveDirectoryIterator($libDir);
$files = new RecursiveIteratorIterator($files, RecursiveIteratorIterator::LEAVES_ONLY);

include_once $libDir . '/Sabre/autoload.php';

// Finding all classnames
$classNames = findClassNames($files);
echo "Found: " . count($classNames) . " classes and interfaces\n";

echo "Generating class tree\n";
$classTree = getClassTree($classNames);

$packageList = array();

foreach($classNames as $className) {

    echo "Creating docs for: " . $className . "\n";

    $output = createDoc($className,isset($classTree[$className])?$classTree[$className]:array());
    file_put_contents($outputDir . '/' . $className . '.wiki', $output);

}

echo "Creating indexes\n";
$output = createSidebarIndex($packageList);
file_put_contents($outputDir . '/APIIndex.wiki', $output);


function findClassNames($files) {

    $classNames = array();
    foreach($files as $fileName=>$fileInfo) {

        $tokens = token_get_all(file_get_contents($fileName));
        foreach($tokens as $tokenIndex=>$token) {

            if ($token[0]===T_CLASS || $token[0]===T_INTERFACE) {
                $classNames[] = $tokens[$tokenIndex+2][1];
            }

        }

    }

    return $classNames;

}

function getClassTree($classNames) {

    $classTree = array();

    foreach($classNames as $className) {

        if (!class_exists($className) && !interface_exists($className)) continue;
        $rClass = new ReflectionClass($className);

        $parent = $rClass->getParentClass();
        if ($parent) $parent = $parent->name;

        if (!isset($classTree[$parent])) $classTree[$parent] = array();
        $classTree[$parent][] = $className;

        foreach($rClass->getInterfaceNames() as $interface) {

            if (!isset($classTree[$interface])) {
                $classTree[$interface] = array();
            }
            $classTree[$interface][] = $className;

        }

    }
    return $classTree;

}

function createDoc($className, $extendedBy) {

    // ew
    global $packageList;

    ob_start();
    $rClass = new ReflectionClass($className);

    echo "#summary API documentation for: ", $rClass->getName() , "\n";
    echo "#labels APIDoc\n";
    echo "#sidebar APIIndex\n";
    echo "=`" . $rClass->getName() . "`=\n";
    echo "\n";

    $docs = parseDocs($rClass->getDocComment());
    echo $docs['description'] . "\n";
    echo "\n";

    $parentClass = $rClass->getParentClass();

    if($parentClass) {
        echo "  * Parent class: [" . $parentClass->getName() . "]\n";
    }
    if ($interfaces = $rClass->getInterfaceNames()) {
        $interfaces = array_map(function($int) { return '[' . $int . ']'; },$interfaces);
        echo "  * Implements: " . implode(", ", $interfaces) . "\n";
    }
    $classType = $rClass->isInterface()?'interface':'class';
    if (isset($docs['deprecated'])) {
        echo "  * *Warning: This $classType is deprecated, and should not longer be used.*\n";
    }
    if ($rClass->isInterface()) {
        echo "  * This is an interface.\n";
    } elseif ($rClass->isAbstract()) {
        echo "  * This is an abstract class.\n";
    }
    if (isset($docs['package'])) {
        $package = $docs['package'];
        if (isset($docs['subpackage'])) {
            $package.='_' . $docs['subpackage'];
        }
        if (!isset($packageList[$package])) {
            $packageList[$package] = array();
        }
        $packageList[$package][] = $rClass->getName();
    }

    if ($extendedBy) {

        echo "\n";
        if ($classType==='interface') {
            echo "This interface is extended by the following interfaces:\n";
            foreach($extendedBy as $className) {
                if (interface_exists($className)) {
                    echo "  * [" . $className . "]\n";
                }
            }
            echo "\n";
            echo "This interface is implemented by the following classes:\n";
        } else {
            echo "This class is extended by the following classes:\n";
        }
        foreach($extendedBy as $className) {
            if (class_exists($className)) {
                echo "  * [" . $className . "]\n";
            }
        }
        echo "\n";

    }
    echo "\n";

    echo "==Properties==\n";

    echo "\n";

    $properties = $rClass->getProperties(ReflectionProperty::IS_STATIC | ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

    if (count($properties)>0) {
        foreach($properties as $rProperty) {

            createPropertyDoc($rProperty);

        }
    } else {
        echo "This $classType does not define any public or protected properties.\n";
    }

    echo "\n";

    echo "==Methods==\n";

    echo "\n";

    $methods = $rClass->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

    if (count($methods)>0) {
        foreach($methods as $rMethod) {

            createMethodDoc($rMethod, $rClass);

        }
    } else {
        echo "\nThis $classType does not define any public or protected methods.\n";
    }

    return ob_get_clean();

}

function createMethodDoc($rMethod, $rClass) {

    echo "===`" . $rMethod->getName() . "`===\n";
    echo "\n";

    $docs = parseDocs($rMethod->getDocComment());

    $return = isset($docs['return'])?$docs['return']:'void';

    echo "{{{\n";
    echo $return . " " . $rMethod->class . "::" . $rMethod->getName() . "(";
    foreach($rMethod->getParameters() as $parameter) {
        if ($parameter->getPosition()>0) echo ", ";
        if ($class = $parameter->getClass()) {
            echo $class->name . " ";
        } elseif (isset($docs['param'][$parameter->name])) {
            echo $docs['param'][$parameter->name] . " ";
        }

        echo '$' . $parameter->name;

        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            $default = $parameter->getDefaultValue();
            $default = var_export($default,true);
            $default = str_replace("\n","",$default);
            echo " = " . $default;

        }
    }
    echo ")\n";
    echo "}}}\n";
    echo "\n";

    echo $docs['description'] . "\n";

    echo "\n";

    $hasProp = false;
    if (isset($docs['deprecated'])) {
        echo "  * *Warning: This method is deprecated, and should not longer be used.*\n";
        $hasProp = true;
    }
    if ($rMethod->isProtected()) {
        echo "  * This method is protected.\n";
        $hasProp = true;
    }
    if ($rMethod->isPrivate()) {
        echo "  * This method is private.\n";
        $hasProp = true;
    }
    if ($rMethod->isAbstract()) {
        echo "  * This is an abstract method\n";
        $hasProp = true;
    }

    if ($rMethod->class != $rClass->name) {
        echo " * Defined in [" . $rMethod->class . "]\n";
        $hasProp = true;
    }

    if ($hasProp) echo "\n";

}

function createPropertyDoc($rProperty) {

    echo "===`" . $rProperty->getName() . "`===\n";
    echo "\n";

    $docs = parseDocs($rProperty->getDocComment());

    $visibility = 'public';
    if ($rProperty->isProtected()) $visibility = 'protected';
    if ($rProperty->isPrivate()) $visibility = 'private';

    echo "{{{\n";
    echo $visibility . " " . $rProperty->class . "::$" . $rProperty->getName();
    echo "\n}}}\n";
    echo "\n";

    echo $docs['description'] . "\n";

    echo "\n";

    $hasProp = false;
    if (isset($docs['deprecated'])) {
        echo "  * *Warning: This property is deprecated, and should not longer be used.*\n";
        $hasProp = true;
    }
    if ($rProperty->isProtected()) {
        echo "  * This property is protected.\n";
        $hasProp = true;
    }
    if ($rProperty->isPrivate()) {
        echo "  * This property is private.\n";
        $hasProp = true;
    }
    if ($rProperty->isStatic()) {
        echo "  * This property is static.\n";
        $hasProp = true;
    }

    if ($hasProp) echo "\n";

}

function parseDocs($docString) {

    $params = array();
    $description = array();

    // Trimming all the comment characters
    $docString = trim($docString,"\n*/ ");
    $docString = explode("\n",$docString);

    foreach($docString as $str) {

        $str = ltrim($str,'* ');
        $str = trim($str);
        if ($str && $str[0]==='@') {
            $r = explode(' ',substr($str,1),2);
            $paramName = $r[0];
            $paramValue = (count($r)>1)?$r[1]:'';

            // 'param' paramName is special. Confusing, I know.
            if ($paramName==='param') {
                if (!isset($params['param'])) $params['param'] = array();
                $paramValue = explode(' ', $paramValue,3);
                $params['param'][substr($paramValue[1],1)] = $paramValue[0];
            } else {
                $params[$paramName] = trim($paramValue);
            }
        } else {
            $description[]=$str;
        }

    }

    $params['description'] = trim(implode("\n",$description),"\n ");

    return $params;

}

function createSidebarIndex($packageList) {

    ob_start();
    echo "#labels APIDocs\n";
    echo "#summary List of all classes, neatly organized\n";
    echo "=API Index=\n";

    foreach($packageList as $package=>$classes) {

        echo "  * $package\n";
        sort($classes);
        foreach($classes as $class) {

            echo "    * [$class $class]\n";

        }

    }

    return ob_get_clean();

}
