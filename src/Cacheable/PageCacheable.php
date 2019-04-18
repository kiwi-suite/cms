<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Package\Cms\Cacheable;

use Ixocreate\Package\Cms\Repository\PageRepository;
use Ixocreate\Package\Type\Entity\UuidType;
use Ixocreate\Cache\CacheableInterface;

final class PageCacheable implements CacheableInterface
{
    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var UuidType|string
     */
    private $pageId;

    /**
     * SitemapCacheable constructor.
     * @param PageRepository $pageRepository
     */
    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;
    }

    public function withPageId($pageId): PageCacheable
    {
        $cacheable = clone $this;
        $cacheable->pageId = $pageId;

        return $cacheable;
    }

    /**
     * @return mixed
     */
    public function uncachedResult()
    {
        return $this->pageRepository->find($this->pageId);
    }

    /**
     * @return string
     */
    public function cacheName(): string
    {
        return 'cms';
    }

    /**
     * @return string
     */
    public function cacheKey(): string
    {
        return 'page.' . (string) $this->pageId;
    }

    /**
     * @return int
     */
    public function cacheTtl(): int
    {
        return 3600;
    }
}
