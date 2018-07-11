<?php
namespace KiwiSuite\Cms\Navigation;


use KiwiSuite\Cms\Entity\Navigation;
use KiwiSuite\Cms\Entity\Page;
use KiwiSuite\Cms\Repository\NavigationRepository;

final class Container implements \Iterator
{
    /**
     * @var Item[]
     */
    private $children;
    /**
     * @var NavigationRepository
     */
    private $navigationRepository;

    /**
     * Item constructor.
     * @param NavigationRepository $navigationRepository
     * @param array $children
     */
    public function __construct(NavigationRepository $navigationRepository, array $children)
    {
        $this->children = $children;
        $this->navigationRepository = $navigationRepository;
    }

    /**
     * @return Item[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return (count($this->children) > 0);
    }

    /**
     * @param int $level
     * @return Container
     */
    public function withMinimumLevel(int $level): Container
    {
        return new Container($this->navigationRepository, $this->recursiveMinimum($this->children, [], $level));
    }

    /**
     * @param Item[] $items
     * @param array $collector
     * @param int $level
     * @return array
     */
    private function recursiveMinimum(array $items, array $collector, int $level): array
    {
        foreach ($items as $item) {
            if ($item->level() === $level) {
                $collector[] = $item;
                continue;
            }

            if ($item->level() < $level) {
                $collector = $this->recursiveMinimum($item->children(), $collector, $level);
            }
        }

        return $collector;
    }

    public function withMaximumLevel(int $level): Container
    {
        return new Container($this->navigationRepository, $this->recursiveMaximum($this->children(), $level));
    }

    /**
     * @param Item[] $items
     * @param array $collector
     * @param int $level
     * @return array
     */
    private function recursiveMaximum(array $items, int $level): array
    {
        $collector = [];
        foreach ($items as $item) {
            if ($item->level() > $level) {
                $collector[] = new Item($item->page(), $item->sitemap(), $item->level(), [], $item->isActive());
                continue;
            }

            $collector[] = new Item(
                $item->page(),
                $item->sitemap(),
                $item->level(),
                $this->recursiveMaximum($item->children(), $level),
                $item->isActive()
            );
        }

        return $collector;
    }

    public function withActiveState(Page $page): Container
    {
        return new Container($this->navigationRepository, $this->recursiveActiveState($this->children(), $page));
    }

    /**
     * @param Item[] $items
     * @param Page $page
     * @return Item[]
     */
    private function recursiveActiveState(array $items, Page $page): array
    {
        $collection = [];
        foreach ($items as $item) {
            if ((string)$page->id() === (string) $item->page()->id()) {
                $collection[] = new Item($item->page(), $item->sitemap(), $item->level(), $item->children(), true);
                continue;
            }

            $children = $this->recursiveActiveState($item->children(), $page);

            foreach ($children as $childItem) {
                if ($childItem->isActive() === true) {
                    $collection[] = new Item($item->page(), $item->sitemap(), $item->level(), $item->children(), true);
                    continue 2;
                }
            }

            $collection[] = $item;
        }

        return $collection;
    }

    public function withOnlyActiveBranch(): ?Item
    {
        foreach ($this->children() as $item) {
            if ($item->isActive()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return Container
     */
    public function withNavigation(string $name): Container
    {
        $pageIds = [];
        $result = $navs = $this->navigationRepository->findBy(['navigation' => $name]);
        /** @var Navigation $navigationEntity */
        foreach ($result as $navigationEntity) {
            $pageIds[] = (string)$navigationEntity->pageId();
        }

        $container = new Container($this->navigationRepository, $this->recursiveNavigation($this->children, $pageIds));
        return $container;
    }

    /**
     * @param Item[] $items
     * @param array $pageIds
     * @return array
     */
    private function recursiveNavigation(array $items, array $pageIds): array
    {
        $collection = [];
        foreach ($items as $item) {
            if (!\in_array((string)$item->page()->id(), $pageIds)) {
                continue;
            }

            $children = $this->recursiveNavigation($item->children(), $pageIds);
            $collection[] = new Item($item->page(), $item->sitemap(), $item->level(), $children, $item->isActive());
        }

        return $collection;
    }

    public function withBreadcrumb(): Item
    {
        return $this->recursiveBreadCrumb($this->children());
    }

    /**
     * @param Item[] $items
     * @return Item
     */
    private function recursiveBreadCrumb(array $items): ?Item
    {
        foreach ($items as $item) {
            if (!$item->isActive()) {
                continue;
            }

            $child = $this->recursiveBreadCrumb($item->children());
            $child = (empty($child)) ? [] : [$child];
            return new Item($item->page(), $item->sitemap(), $item->level(), $child, $item->isActive());
        }

        return null;
    }

    /**
     * @return Item
     */
    public function current()
    {
        return current($this->children);
    }

    /**
     *
     */
    public function next()
    {
        next($this->children);
    }

    /**
     * @return int|mixed|null|string
     */
    public function key()
    {
        return key($this->children);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $key = key($this->children);
        return ($key !== null && $key !== false);
    }

    /**
     *
     */
    public function rewind()
    {
        reset($this->children);
    }

    public function __debugInfo()
    {
        return $this->children();
    }
}