<?php

namespace Ajthinking\Tinx;

class Model
{
    public function __construct($classWithFullNamespace) {
        $this->classWithFullNamespace = $classWithFullNamespace;
        $parts = explode('\\',$classWithFullNamespace);
        $this->className = end($parts);
        $this->slug = str_slug($this->className);        
    }

    public static function all()
    {
        // This should be moved to a publishable config file
        $namespacesAndPaths = [
            "App" => "/app",
            "App\Models" => "/app/Models/*"
        ];

        $models = collect();
        foreach ($namespacesAndPaths as $modelFilePath) {
            $absoluteFilePath = self::getAbsoluteFilePath($modelFilePath);
            $recursive = false;
            if (ends_with($absoluteFilePath, '*')) {
                $absoluteFilePath = rtrim($absoluteFilePath, '/*');
                $recursive = true;
            }
            if (false === file_exists($absoluteFilePath)) {
                continue;
            }
            foreach (self::getVisibleFiles($absoluteFilePath, $recursive) as $filePath) {
                $fullClassName = self::getFullClassName($filePath);
                if (self::shouldNotInclude($filePath, $fullClassName)) {
                    continue;
                }
                $models->push(new Model($fullClassName));
            }
        }
        return $models;
    }

    /**
     * @param string $filePath
     * @return string
     * */
    public static function getAbsoluteFilePath($path)
    {
        return base_path(trim($path, '/'));
    }
    /**
     * @param string $filePath
     * @param string $fullClassName
     * @return bool
     * */
    public static function shouldNotInclude($filePath, $fullClassName)
    {
     if (strpos($fullClassName, 'Scopes')) {
         return true;
     }
     return false;
    }

    /**
     * @param string $path
     * @param bool $recursive
     * @return array
     */
    public static function getVisibleFiles($path, $recursive = false)
    {
        $method = $recursive ? 'allFiles' : 'files';
        return collect(app('files')->$method($path))->map(function ($file) {
            return is_string($file) ? $file : $file->getRealPath();
        })->values();
    }

    /**
     * @param string $path
     * @return $string
     * */
    public static function getFullClassName($path)
    {
        $matches = [];
        try {
            preg_match(self::getNamespaceRegex(), app('files')->get($path), $matches);
        } catch (Exception $e) {
            // Fail silently…
        }
        $namespace = array_get($matches, 1);
        if (null === $namespace) {
            return null;
        }
        return $namespace . '\\' . app('files')->name($path);
    }

    /**
     * @return string
     * */
    private static function getNamespaceRegex()
    {
        $start = $end = '/';
        $wordBoundary = '\b';
        $oneOrMoreSpaces = '\s+';
        $oneOrMoreWordsOrSlashes = '[\w|\\\]+';
        $zeroOrMoreSpaces = '\s*';
        $startGroup = '(';
        $endGroup = ')';
        $ignoreCase = 'i';
        return
            $start.
            $wordBoundary.'namespace'.$wordBoundary.$oneOrMoreSpaces.
            $startGroup.$oneOrMoreWordsOrSlashes.$endGroup.
            $zeroOrMoreSpaces.
            ';'.
            $end.
            $ignoreCase;
    }

    public function empty()
    {
        return ! (boolean) $this->classWithFullNamespace::first();
    }

    public function sample()
    {
        return $this->classWithFullNamespace::first();
    }

    // attributes - hidden attributes as non associative array
    public function publicAttributes() {        
        return array_values(
            array_diff(
                array_keys(
                    $this->sample()->getAttributes()
                ),
                $this->sample()->getHidden()
            )
        );
    }

    public function hasTable() {

    }
}
