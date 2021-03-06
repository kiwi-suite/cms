<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Router\Replacement;

use Ixocreate\Cms\Router\RouteSpecification;
use Ixocreate\Cms\Router\RoutingItem;

final class SlugReplacement implements ReplacementInterface
{
    /**
     * @return int
     */
    public function priority(): int
    {
        return 1;
    }

    /**
     * @param RouteSpecification $routeSpecification
     * @param string $locale
     * @param RoutingItem $item
     * @throws \Psr\Cache\InvalidArgumentException
     * @return RouteSpecification
     */
    public function replace(
        RouteSpecification $routeSpecification,
        string $locale,
        RoutingItem $item
    ): RouteSpecification {
        $page = $item->page($locale);
        if (!empty($page->slug())) {
            foreach ($routeSpecification->uris() as $name => $uri) {
                $routeSpecification = $routeSpecification->withUri(\str_replace('${SLUG}', $page->slug(), $uri), $name);
            }
        }

        return $routeSpecification;
    }
}
