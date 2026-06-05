<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Exception\DirectoryNotFound;
use Ray\Di\AbstractModule;
use Ray\Di\Name;
use ReflectionClass;
use ReflectionProperty;

use function array_key_exists;
use function array_keys;
use function array_slice;
use function class_exists;
use function class_implements;
use function count;
use function explode;
use function file_get_contents;
use function glob;
use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function ltrim;
use function rtrim;
use function sprintf;
use function strrpos;
use function substr;
use function token_get_all;

use const DIRECTORY_SEPARATOR;
use const T_AS;
use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_DOUBLE_COLON;
use const T_EXTENDS;
use const T_IMPLEMENTS;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAME_RELATIVE;
use const T_NAMESPACE;
use const T_NEW;
use const T_NS_SEPARATOR;
use const T_OPEN_TAG;
use const T_STRING;
use const T_USE;
use const T_WHITESPACE;

/**
 * @psalm-type ParsedClass = array{class: string, file: string, extends: ?string, implements: list<string>}
 *
 * Binds concrete Injectable classes discovered from flat Yii directories.
 */
final class InjectableModule extends AbstractModule
{
    /** @var string[] */
    private array $directories;

    /**
     * @param string[] $directories
     */
    public function __construct(array $directories, ?AbstractModule $module = null)
    {
        $this->directories = $directories;

        parent::__construct($module);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $classes = [];
        foreach ($this->directories as $directory) {
            foreach ($this->parseClassesInDirectory($directory) as $class => $metadata) {
                $classes[$class] = $metadata;
            }
        }

        $injectableClasses = $this->findInjectableClasses($classes);
        $loaded = [];
        foreach (array_keys($injectableClasses) as $class) {
            $this->loadAndBindCandidate($class, $classes, $injectableClasses, $loaded);
        }
    }

    /**
     * @return array<string, ParsedClass>
     */
    private function parseClassesInDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new DirectoryNotFound(sprintf('Directory not found: %s', $directory));
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');
        if (! is_array($files)) {
            return [];
        }

        $classes = [];
        foreach ($files as $file) {
            foreach ($this->parseClassesInFile($file) as $metadata) {
                $classes[$metadata['class']] = $metadata;
            }
        }

        return $classes;
    }

    private function implementsInjectable(string $class): bool
    {
        $interfaces = class_implements($class, false);
        if (! is_array($interfaces)) {
            return false;
        }

        return in_array(Injectable::class, $interfaces, true);
    }

    private function isBound(string $class): bool
    {
        $index = $class . '-' . Name::ANY;
        foreach ($this->moduleChain() as $module) {
            if (! array_key_exists($index, $module->getContainer()->getContainer())) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param array<string, ParsedClass> $classes
     *
     * @return array<string, true>
     */
    private function findInjectableClasses(array $classes): array
    {
        $injectableClasses = [];
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($classes as $class => $metadata) {
                if (isset($injectableClasses[$class])) {
                    continue;
                }

                if ($this->explicitlyImplementsInjectable($metadata)) {
                    $injectableClasses[$class] = true;
                    $changed = true;

                    continue;
                }

                if (! $this->extendsKnownInjectable($metadata, $injectableClasses)) {
                    continue;
                }

                $injectableClasses[$class] = true;
                $changed = true;
            }
        }

        return $injectableClasses;
    }

    /** @param array{class: string, file: string, extends: ?string, implements: list<string>} $metadata */
    private function explicitlyImplementsInjectable(array $metadata): bool
    {
        return in_array(Injectable::class, $metadata['implements'], true);
    }

    /**
     * @param array{class: string, file: string, extends: ?string, implements: list<string>} $metadata
     * @param array<string,true>                                                             $injectableClasses
     */
    private function extendsKnownInjectable(array $metadata, array $injectableClasses): bool
    {
        $parent = $metadata['extends'];
        if ($parent === null) {
            return false;
        }

        if (isset($injectableClasses[$parent])) {
            return true;
        }

        if (! class_exists($parent, false)) {
            return false;
        }

        return $this->implementsInjectable($parent);
    }

    /**
     * @param array<string, ParsedClass> $classes
     * @param array<string, true>        $injectableClasses
     * @param array<string, true>        $loaded
     */
    private function loadAndBindCandidate(string $class, array $classes, array $injectableClasses, array &$loaded): void
    {
        if (isset($loaded[$class])) {
            return;
        }

        if (! isset($classes[$class])) {
            return;
        }

        $metadata = $classes[$class];
        $parent = $metadata['extends'];
        if ($parent !== null && isset($injectableClasses[$parent])) {
            $this->loadAndBindCandidate($parent, $classes, $injectableClasses, $loaded);
        }

        require_once $metadata['file'];
        $loaded[$class] = true;
        if (! class_exists($class, false)) {
            return;
        }

        if (! $this->implementsInjectable($class)) {
            return;
        }

        if (! (new ReflectionClass($class))->isInstantiable()) {
            return;
        }

        if ($this->isBound($class)) {
            return;
        }

        $this->bind($class);
    }

    /**
     * @return list<ParsedClass>
     */
    private function parseClassesInFile(string $file): array
    {
        $source = file_get_contents($file);
        if (! is_string($source)) {
            return [];
        }

        $tokens = token_get_all($source);
        $namespace = '';
        $uses = [];
        $classes = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
                $uses = [];

                continue;
            }

            if ($token[0] === T_USE && $classes === [] && $this->isNamespaceUse($tokens, $i)) {
                foreach ($this->readUseAliases($tokens, $i + 1) as $alias => $class) {
                    $uses[$alias] = $class;
                }

                continue;
            }

            if ($token[0] !== T_CLASS) {
                continue;
            }

            if (! $this->isClassDeclaration($tokens, $i)) {
                continue;
            }

            $metadata = $this->readClassMetadata($tokens, $i + 1, $namespace, $uses, $file);
            if ($metadata === null) {
                continue;
            }

            $classes[] = $metadata;
        }

        return $classes;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     */
    private function readNamespace(array $tokens, int $offset): string
    {
        $namespace = '';
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === ';' || $token === '{') {
                return $namespace;
            }

            if (! is_array($token)) {
                continue;
            }

            if (! $this->isNameToken($token[0])) {
                continue;
            }

            $namespace .= $token[1];
        }

        return $namespace;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     * @param array<string, string>                            $uses
     *
     * @return array{class: string, file: string, extends: ?string, implements: list<string>}|null
     */
    private function readClassMetadata(array $tokens, int $offset, string $namespace, array $uses, string $file): ?array
    {
        $className = $this->readClassName($tokens, $offset);
        if ($className === null) {
            return null;
        }

        [$extends, $implements] = $this->readClassRelationships($tokens, $className['index'] + 1, $namespace, $uses);

        return [
            'class' => $this->resolveDeclaredClassName($className['name'], $namespace),
            'file' => $file,
            'extends' => $extends,
            'implements' => $implements,
        ];
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     *
     * @return array{name: string, index: int}|null
     */
    private function readClassName(array $tokens, int $offset): ?array
    {
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_WHITESPACE) {
                continue;
            }

            if ($token[0] === T_STRING) {
                return ['name' => $token[1], 'index' => $i];
            }

            return null;
        }

        return null;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     * @param array<string, string>                            $uses
     *
     * @return array{0: ?string, 1: list<string>}
     */
    private function readClassRelationships(array $tokens, int $offset, string $namespace, array $uses): array
    {
        $extends = null;
        $implements = [];
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === '{') {
                return [$extends, $implements];
            }

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_EXTENDS) {
                $name = $this->readName($tokens, $i + 1);
                if ($name === null) {
                    continue;
                }

                $extends = $this->resolveName($name['name'], $namespace, $uses);
                $i = $name['index'];

                continue;
            }

            if ($token[0] !== T_IMPLEMENTS) {
                continue;
            }

            $implements = $this->readImplementedNames($tokens, $i + 1, $namespace, $uses);

            return [$extends, $implements];
        }

        return [$extends, $implements];
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     *
     * @return array{name: string, index: int}|null
     */
    private function readName(array $tokens, int $offset): ?array
    {
        $name = '';
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_WHITESPACE && $name === '') {
                continue;
            }

            if (is_array($token) && $this->isNameToken($token[0])) {
                $name .= $token[1];

                continue;
            }

            if ($name === '') {
                return null;
            }

            return ['name' => $name, 'index' => $i - 1];
        }

        if ($name === '') {
            return null;
        }

        return ['name' => $name, 'index' => $count - 1];
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     * @param array<string, string>                            $uses
     *
     * @return list<string>
     */
    private function readImplementedNames(array $tokens, int $offset, string $namespace, array $uses): array
    {
        $implements = [];
        $name = '';
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === '{') {
                $this->addResolvedName($implements, $name, $namespace, $uses);

                return $implements;
            }

            if ($token === ',') {
                $this->addResolvedName($implements, $name, $namespace, $uses);
                $name = '';

                continue;
            }

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_WHITESPACE) {
                continue;
            }

            if (! $this->isNameToken($token[0])) {
                continue;
            }

            $name .= $token[1];
        }

        $this->addResolvedName($implements, $name, $namespace, $uses);

        return $implements;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     *
     * @return array<string, string>
     */
    private function readUseAliases(array $tokens, int $offset): array
    {
        $uses = [];
        $name = '';
        $alias = '';
        $readingAlias = false;
        $count = count($tokens);
        for ($i = $offset; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token === ';') {
                $this->addUseAlias($uses, $name, $alias);

                return $uses;
            }

            if ($token === ',') {
                $this->addUseAlias($uses, $name, $alias);
                $name = '';
                $alias = '';
                $readingAlias = false;

                continue;
            }

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_AS) {
                $readingAlias = true;

                continue;
            }

            if (! $this->isNameToken($token[0])) {
                continue;
            }

            if ($readingAlias) {
                $alias .= $token[1];

                continue;
            }

            $name .= $token[1];
        }

        $this->addUseAlias($uses, $name, $alias);

        return $uses;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     */
    private function isClassDeclaration(array $tokens, int $classIndex): bool
    {
        for ($i = $classIndex - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (! is_array($token)) {
                return true;
            }

            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return ! in_array($token[0], [T_NEW, T_DOUBLE_COLON], true);
        }

        return true;
    }

    /**
     * @param array<int, array{0:int, 1:string, 2:int}|string> $tokens
     */
    private function isNamespaceUse(array $tokens, int $useIndex): bool
    {
        for ($i = $useIndex - 1; $i >= 0; $i--) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            if (is_array($token) && $token[0] === T_OPEN_TAG) {
                return true;
            }

            return $token === ';' || $token === '{';
        }

        return true;
    }

    private function isNameToken(int $token): bool
    {
        return in_array($token, [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true);
    }

    private function resolveDeclaredClassName(string $class, string $namespace): string
    {
        if ($namespace === '') {
            return $class;
        }

        return $namespace . '\\' . $class;
    }

    /**
     * @param array<string, string> $uses
     */
    private function resolveName(string $name, string $namespace, array $uses): string
    {
        if ($name[0] === '\\') {
            return ltrim($name, '\\');
        }

        $parts = explode('\\', $name);
        $alias = $parts[0];
        if (isset($uses[$alias])) {
            if (count($parts) === 1) {
                return $uses[$alias];
            }

            return $uses[$alias] . '\\' . implode('\\', array_slice($parts, 1));
        }

        if ($namespace === '') {
            return $name;
        }

        return $namespace . '\\' . $name;
    }

    /**
     * @param list<string>          $names
     * @param array<string, string> $uses
     *
     * @psalm-param-out list<string> $names
     */
    private function addResolvedName(array &$names, string $name, string $namespace, array $uses): void
    {
        if ($name === '') {
            return;
        }

        $names[] = $this->resolveName($name, $namespace, $uses);
    }

    /**
     * @param array<string, string> $uses
     *
     * @psalm-param-out array<string, string> $uses
     */
    private function addUseAlias(array &$uses, string $name, string $alias): void
    {
        if ($name === '') {
            return;
        }

        $class = ltrim($name, '\\');
        $uses[$alias !== '' ? $alias : $this->shortName($class)] = $class;
    }

    private function shortName(string $class): string
    {
        $position = strrpos($class, '\\');
        if ($position === false) {
            return $class;
        }

        return substr($class, $position + 1);
    }

    /**
     * Ray.Di has no public is-bound API. Container::getContainer() is public,
     * but its string keys are Ray.Di's internal binding convention.
     *
     * @return list<AbstractModule>
     */
    private function moduleChain(): array
    {
        $modules = [$this];
        $module = $this->lastModule;
        while ($module instanceof AbstractModule) {
            $modules[] = $module;
            $module = $this->readLastModule($module);
        }

        return $modules;
    }

    private function readLastModule(AbstractModule $module): ?AbstractModule
    {
        $property = new ReflectionProperty(AbstractModule::class, 'lastModule');
        $property->setAccessible(true);
        $lastModule = $property->getValue($module);
        if (! $lastModule instanceof AbstractModule) {
            return null;
        }

        return $lastModule;
    }
}
