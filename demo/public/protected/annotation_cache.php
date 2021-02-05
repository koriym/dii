<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\PhpFileCache;
use Ray\ServiceLocator\ServiceLocator;

// PhpFile cache
$cache = new PhpFileCache(__DIR__ . '/cache');
ServiceLocator::setReader(new CachedReader(new AnnotationReader(), $cache));