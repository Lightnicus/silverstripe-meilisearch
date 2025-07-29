<?php

namespace BimTheBam\Meilisearch\Model\Extension;

use BimTheBam\Meilisearch\Index;
use BimTheBam\Meilisearch\Model\Document;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SilverStripe\Core\Extension;
use Throwable;

/**
 * Class Searchable
 * @package BimTheBam\Meilisearch\Model\Extension
 */
class Searchable extends Extension
{
    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function onAfterWrite(): void
    {
        $indices = Index::for_class($this->owner::class);
        foreach ($indices as $index) {
            $index->add(Document::create($this->owner));
        }
    }

    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function onAfterDelete(): void
    {
        $indices = Index::for_class($this->owner::class);
        foreach ($indices as $index) {
            $index->remove(Document::create($this->owner));
        }
    }
}
