<?php

declare(strict_types=1);

namespace Koriym\Dii;

use Koriym\Dii\Exception\DirectoryNotFoundException;
use Ray\Di\AbstractModule;
use Ray\Di\Name;

use function class_exists;
use function class_implements;
use function count;
use function file_get_contents;
use function glob;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function rtrim;
use function sprintf;
use function token_get_all;

use const DIRECTORY_SEPARATOR;
use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_DOUBLE_COLON;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_NEW;
use const T_NS_SEPARATOR;
use const T_STRING;
use const T_WHITESPACE;

/**
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
        foreach ($this->directories as $directory) {
            $this->bindInjectablesInDirectory($directory);
        }
    }

    private function bindInjectablesInDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            throw new DirectoryNotFoundException(sprintf('Directory not found: %s', $directory));
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');
        if (! is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            $class = $this->extractClass($file);
            if ($class === null) {
                continue;
            }

            require_once $file;

            if (! class_exists($class)) {
                continue;
            }

            if (! $this->implementsInjectable($class)) {
                continue;
            }

            if ($this->isBound($class)) {
                continue;
            }

            $this->bind($class);
        }
    }

    private function implementsInjectable(string $class): bool
    {
        $interfaces = class_implements($class);
        if (! is_array($interfaces)) {
            return false;
        }

        return in_array(Injectable::class, $interfaces, true);
    }

    private function isBound(string $class): bool
    {
        $index = $class . '-' . Name::ANY;
        if (isset($this->getContainer()->getContainer()[$index])) {
            return true;
        }

        if (! $this->lastModule instanceof AbstractModule) {
            return false;
        }

        return isset($this->lastModule->getContainer()->getContainer()[$index]);
    }

    private function extractClass(string $file): ?string
    {
        $source = file_get_contents($file);
        if (! is_string($source)) {
            return null;
        }

        $tokens = token_get_all($source);
        $namespace = '';
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = $this->readNamespace($tokens, $i + 1);
                continue;
            }

            if ($token[0] !== T_CLASS) {
                continue;
            }

            if (! $this->isClassDeclaration($tokens, $i)) {
                continue;
            }

            $class = $this->readClassName($tokens, $i + 1);
            if ($class === null) {
                return null;
            }

            if ($namespace === '') {
                return $class;
            }

            return $namespace . '\\' . $class;
        }

        return null;
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
     */
    private function readClassName(array $tokens, int $offset): ?string
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
                return $token[1];
            }

            return null;
        }

        return null;
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

    private function isNameToken(int $token): bool
    {
        return in_array($token, [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true);
    }
}
