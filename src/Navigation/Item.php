<?php
namespace KiwiSuite\Cms\Navigation;

use KiwiSuite\Cms\Entity\Page;
use KiwiSuite\Cms\Entity\Sitemap;

final class Item
{
    /**
     * @var Page
     */
    private $page;
    /**
     * @var Sitemap
     */
    private $sitemap;
    /**
     * @var int
     */
    private $level;
    /**
     * @var bool
     */
    private $active;

    /**
     * @var Item[]
     */
    private $children;

    /**
     * Item constructor.
     * @param Page $page
     * @param Sitemap $sitemap
     * @param int $level
     * @param array $children
     * @param bool $active
     */
    public function __construct(Page $page, Sitemap $sitemap, int $level, array $children, $active = false)
    {
        $this->page = $page;
        $this->sitemap = $sitemap;
        $this->level = $level;
        $this->children = $children;
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function level(): int
    {
        return $this->level;
    }

    /**
     * @return Page
     */
    public function page(): Page
    {
        return $this->page;
    }

    /**
     * @return Sitemap
     */
    public function sitemap(): Sitemap
    {
        return $this->sitemap;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return Item[]
     */
    final public function children(): array
    {
        return $this->children;
    }

    final public function hasChildren(): bool
    {
        return (count($this->children) > 0);
    }
}