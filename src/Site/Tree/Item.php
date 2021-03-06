<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Site\Tree;

use Ixocreate\Cache\CacheableInterface;
use Ixocreate\Cache\CacheManager;
use Ixocreate\Cms\Cacheable\PageCacheable;
use Ixocreate\Cms\Cacheable\PageVersionCacheable;
use Ixocreate\Cms\Cacheable\SitemapCacheable;
use Ixocreate\Cms\Entity\Page;
use Ixocreate\Cms\Entity\PageVersion;
use Ixocreate\Cms\Entity\Sitemap;
use Ixocreate\Cms\PageType\PageTypeInterface;
use Ixocreate\Cms\PageType\PageTypeSubManager;
use Ixocreate\Cms\Router\PageRoute;
use Ixocreate\Cms\Site\Structure\StructureItem;
use Ixocreate\Schema\Type\SchemaType;
use Ixocreate\Schema\Type\Type;
use Ixocreate\ServiceManager\SubManager\SubManagerInterface;
use RecursiveIterator;

class Item implements ContainerInterface
{
    /**
     * @var StructureItem
     */
    private $structureItem;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ItemFactory
     */
    private $itemFactory;

    /**
     * @var PageCacheable
     */
    private $pageCacheable;

    /**
     * @var SitemapCacheable
     */
    private $sitemapCacheable;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var PageTypeSubManager
     */
    private $pageTypeSubManager;

    /**
     * @var PageVersionCacheable
     */
    private $pageVersionCacheable;

    /**
     * @var SearchSubManager
     */
    private $searchSubManager;

    /**
     * @var PageRoute
     */
    private $pageRoute;

    /**
     * Item constructor.
     * @param StructureItem $structureItem
     * @param ItemFactory $itemFactory
     * @param CacheableInterface $pageCacheable
     * @param CacheableInterface $sitemapCacheable
     * @param CacheableInterface $pageVersionCacheable
     * @param CacheManager $cacheManager
     * @param SubManagerInterface $pageTypeSubManager
     * @param SubManagerInterface $searchSubManager
     * @param PageRoute $pageRoute
     */
    public function __construct(
        StructureItem $structureItem,
        ItemFactory $itemFactory,
        CacheableInterface $pageCacheable,
        CacheableInterface $sitemapCacheable,
        CacheableInterface $pageVersionCacheable,
        CacheManager $cacheManager,
        SubManagerInterface $pageTypeSubManager,
        SubManagerInterface $searchSubManager,
        PageRoute $pageRoute
    ) {
        $this->structureItem = clone $structureItem;
        $this->itemFactory = $itemFactory;
        $this->pageCacheable = $pageCacheable;
        $this->sitemapCacheable = $sitemapCacheable;
        $this->pageVersionCacheable = $pageVersionCacheable;
        $this->cacheManager = $cacheManager;
        $this->pageTypeSubManager = $pageTypeSubManager;
        $this->searchSubManager = $searchSubManager;
        $this->pageRoute = $pageRoute;
    }

    private function container(): Container
    {
        if (empty($this->container)) {
            $this->container = new Container($this->structureItem->children(), $this->searchSubManager, $this->itemFactory);
        }

        return $this->container;
    }

    public function count()
    {
        return $this->container()->count();
    }

    public function structureItem(): StructureItem
    {
        return $this->structureItem;
    }

    /**
     * @return ContainerInterface
     */
    public function below(): ContainerInterface
    {
        return new Container($this->structureItem->children(), $this->searchSubManager, $this->itemFactory);
    }

    /**
     * @return PageTypeInterface
     */
    public function pageType(): PageTypeInterface
    {
        return $this->pageTypeSubManager->get($this->structureItem()->pageType());
    }

    /**
     * @param string $locale
     * @throws \Psr\Cache\InvalidArgumentException
     * @return Page
     */
    public function page(string $locale): Page
    {
        if (!\array_key_exists($locale, $this->structureItem()->pages())) {
            throw new \Exception(\sprintf("Page with locale '%s' does not exists", $locale));
        }

        return $this->cacheManager->fetch(
            $this->pageCacheable
                ->withPageId($this->structureItem()->pages()[$locale])
        );
    }

    /**
     * @param string $locale
     * @param array $params
     * @param string $routePrefix
     * @throws \Psr\Cache\InvalidArgumentException
     * @return string
     */
    public function pageUrl(string $locale, array $params = [], string $routePrefix = ''): string
    {
        try {
            return $this->pageRoute->fromPage($this->page($locale), $params, $routePrefix);
        } catch (\Exception $exception) {
        }

        return '';
    }

    public function sitemap(): Sitemap
    {
        return $this->cacheManager->fetch(
            $this->sitemapCacheable
                ->withSitemapId($this->structureItem()->sitemapId())
        );
    }

    /**
     * @param string $locale
     * @throws \Psr\Cache\InvalidArgumentException
     * @return SchemaType
     */
    public function pageContent(string $locale): SchemaType
    {
        if (!\array_key_exists($locale, $this->structureItem()->pages())) {
            throw new \Exception(\sprintf("Page with locale '%s' does not exists", $locale));
        }

        $pageVersion = $this->cacheManager->fetch(
            $this->pageVersionCacheable
                ->withPageId($this->structureItem()->pages()[$locale])
        );

        if (!($pageVersion instanceof PageVersion)) {
            return Type::create([], SchemaType::serviceName());
        }

        return $pageVersion->content();
    }

    public function level(): int
    {
        return $this->structureItem()->level();
    }

    public function handle(): ?string
    {
        return $this->structureItem->handle();
    }

    public function navigation(string $locale = null): array
    {
        if ($locale === null) {
            $locale = \Locale::getDefault();
        }

        $pages = $this->structureItem()->pages();
        if (empty($pages[$locale])) {
            return [];
        }

        $page = $pages[$locale];

        $navigation = $this->structureItem()->navigation();

        if (empty($navigation[$page])) {
            return [];
        }

        return $navigation[$page];
    }

    /**
     * @param Sitemap|null $currentSitemap
     * @return bool
     */
    public function isActive(?Sitemap $currentSitemap = null): bool
    {
        if (empty($currentSitemap)) {
            return false;
        }

        if ((string) $this->structureItem()->sitemapId() === (string) $currentSitemap->id()) {
            return true;
        }

        if ($currentSitemap->nestedLeft() > $this->sitemap()->nestedLeft() && $currentSitemap->nestedRight() < $this->sitemap()->nestedRight()) {
            return true;
        }

        return false;
    }

    /**
     * @param callable|string $filter
     * @param array $params
     * @return ContainerInterface
     */
    public function filter($filter, array $params = []): ContainerInterface
    {
        return $this->container()->filter($filter, $params);
    }

    /**
     * @param int $level
     * @return ContainerInterface
     */
    public function withMaxLevel(int $level): ContainerInterface
    {
        return $this->container()->withMaxLevel($level);
    }

    /**
     * @param string $navigation
     * @return ContainerInterface
     */
    public function withNavigation(string $navigation): ContainerInterface
    {
        return $this->container()->withNavigation($navigation);
    }

    /**
     * @param callable|string $filter
     * @param array $params
     * @return ContainerInterface
     */
    public function where($filter, array $params = []): ContainerInterface
    {
        return $this->container()->where($filter, $params);
    }

    /**
     * @param int $level
     * @return ContainerInterface
     */
    public function withMinLevel(int $level): ContainerInterface
    {
        return $this->container()->withMinLevel($level);
    }

    /**
     * @return ContainerInterface
     */
    public function flatten(): ContainerInterface
    {
        return $this->container()->flatten();
    }

    /**
     * @param callable|string $filter
     * @param array $params
     * @return Item|null
     */
    public function find($filter, array $params = []): ?Item
    {
        return $this->container()->find($filter, $params);
    }

    /**
     * @param string $handle
     * @return Item|null
     */
    public function findByHandle(string $handle): ?Item
    {
        return $this->container()->findByHandle($handle);
    }

    /**
     * @param callable $callable
     * @return ContainerInterface
     */
    public function sort(callable $callable): ContainerInterface
    {
        return $this->container()->sort($callable);
    }

    public function paginate(int $limit, int $offset = 0): ContainerInterface
    {
        $this->container()->paginate($limit, $offset);
    }

    /**
     * @return Item
     */
    public function current()
    {
        return $this->container()->current();
    }

    /**
     *
     */
    public function next()
    {
        $this->container()->next();
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->container()->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->container()->valid();
    }

    /**
     *
     */
    public function rewind()
    {
        $this->container()->rewind();
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->container()->hasChildren();
    }

    /**
     * @return RecursiveIterator|Item
     */
    public function getChildren()
    {
        return $this->container()->getChildren();
    }
}
