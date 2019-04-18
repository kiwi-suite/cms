<?php
declare(strict_types=1);
namespace Ixocreate\Package\Cms\Router;

final class RouteSpecification
{
    public const NAME_MAIN = "*";
    public const NAME_INHERITANCE = "inheritance";

    /**
     * @var string[]
     */
    private $uris = [];

    /**
     * @var string
     */
    private $pageId;

    /**
     * @var array
     */
    private $middleware = [];

    /**
     * @param string $name
     * @param bool $fallback
     * @return string
     * @throws \Exception
     */
    public function uri(string $name, bool $fallback = true): string
    {
        if (array_key_exists($name, $this->uris)) {
            return $this->uris[$name];
        }

        if ($fallback === true) {
            $name = self::NAME_MAIN;
        }

        if (array_key_exists($name, $this->uris)) {
            return $this->uris[$name];
        }

        throw new \Exception("Invalid Uri");
    }

    /**
     * @return array
     */
    public function uris(): array
    {
        return $this->uris;
    }

    /**
     * @param string $uri
     * @param string $name
     * @return RouteSpecification
     */
    public function withUri(string $uri, string $name = self::NAME_MAIN): RouteSpecification
    {
        $uri = rtrim($uri, "/");
        $routeSpecification = clone $this;
        $routeSpecification->uris[$name] = $uri;

        return $routeSpecification;
    }

    /**
     * @return string
     */
    public function pageId(): string
    {
        return $this->pageId;
    }

    /**
     * @param string $pageId
     * @return RouteSpecification
     */
    public function withPageId(string $pageId): RouteSpecification
    {
        $routeSpecification = clone $this;
        $routeSpecification->pageId = $pageId;

        return $routeSpecification;
    }

    /**
     * @return array
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param array $middleware
     * @return RouteSpecification
     */
    public function withMiddleware(array $middleware): RouteSpecification
    {
        $routeSpecification = clone $this;
        $routeSpecification->middleware = $middleware;

        return $routeSpecification;
    }
}
