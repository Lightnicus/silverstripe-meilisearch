<?php

namespace BimTheBam\Meilisearch\Model\Extension;

use BimTheBam\Meilisearch\Index;
use BimTheBam\Meilisearch\Model\Document;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SilverStripe\ORM\DataExtension;
use Throwable;

/**
 * Class SearchableVersioned
 * @package BimTheBam\Meilisearch\Model\Extension
 */
class SearchableVersioned extends DataExtension
{
    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function onAfterPublish(): void
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
    public function onAfterUnpublish(): void
    {
        $indices = Index::for_class($this->owner::class);
        foreach ($indices as $index) {
            $index->remove(Document::create($this->owner));
        }
    }
}
