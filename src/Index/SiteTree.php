<?php

namespace BimTheBam\Meilisearch\Index;

use BimTheBam\Meilisearch\Index;
use BimTheBam\Meilisearch\Model\CMS\SearchPage;
use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\CMS\Model\RedirectorPage;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\ORM\DataList;
use Throwable;

/**
 * Class SiteTree
 * @package BimTheBam\Meilisearch\Index
 */
class SiteTree extends Index
{
    /**
     * @var string
     */
    private static string $data_object_base_class = \SilverStripe\CMS\Model\SiteTree::class;

    /**
     * @var array|string[]
     */
    private static array $excluded_classes = [
        RedirectorPage::class,
        VirtualPage::class,
        ErrorPage::class,
        SearchPage::class,
    ];

    /**
     * @var bool
     */
    private static bool $check_can_view = true;

    /**
     * @return DataList|null
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    protected function getDataList(): ?DataList
    {
        return parent::getDataList()
            ->filter(['ShowInSearch' => true]);
    }
}
