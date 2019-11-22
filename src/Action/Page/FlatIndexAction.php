<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOLIT GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Action\Page;

use Doctrine\ORM\Query;
use Ixocreate\Admin\Response\ApiErrorResponse;
use Ixocreate\Admin\Response\ApiSuccessResponse;
use Ixocreate\Cms\Entity\Page;
use Ixocreate\Cms\Entity\Sitemap;
use Ixocreate\Cms\PageType\PageTypeInterface;
use Ixocreate\Cms\PageType\PageTypeSubManager;
use Ixocreate\Cms\PageType\RootPageTypeInterface;
use Ixocreate\Cms\PageType\TerminalPageTypeInterface;
use Ixocreate\Cms\Repository\PageRepository;
use Ixocreate\Cms\Repository\SitemapRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FlatIndexAction implements MiddlewareInterface
{
    /**
     * @var SitemapRepository
     */
    private $sitemapRepository;

    /**
     * @var PageTypeSubManager
     */
    private $pageTypeSubManager;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    public function __construct(SitemapRepository $sitemapRepository, PageTypeSubManager $pageTypeSubManager, PageRepository $pageRepository)
    {
        $this->sitemapRepository = $sitemapRepository;
        $this->pageTypeSubManager = $pageTypeSubManager;
        $this->pageRepository = $pageRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $handle = $request->getAttribute('handle');

        $parentSitemap = $this->sitemapRepository->findOneBy(['handle' => $handle]);
        if (empty($parentSitemap)) {
            return new ApiErrorResponse('invalid_handle');
        }

        /**
         * assemble query
         * searchable page name
         */
        $parameters = ['parentId' => (string)$parentSitemap->id()];
        $dql = 'FROM ' . Sitemap::class . ' s LEFT JOIN ' . Page::class . ' p WITH (s.id = p.sitemapId) WHERE s.parentId = :parentId';
        if ($search = $request->getQueryParams()['search'] ?? null) {
            $parameters += ['search' => '%' . $search . '%'];
            $dql .= ' AND p.name LIKE :search';
        }
        $dql .= ' ORDER BY p.releasedAt DESC';

        $count = $this->sitemapRepository->createQuery('SELECT COUNT(s) ' . $dql)
            ->execute($parameters, Query::HYDRATE_SINGLE_SCALAR);

        $result = $this->sitemapRepository->createQuery('SELECT s ' . $dql)
            ->setMaxResults(\min(25, (int)($request->getQueryParams()['limit'] ?? 25)))
            ->setFirstResult(\min((int)($request->getQueryParams()['offset'] ?? 0), $count))
            ->execute($parameters);

        $items = [];
        foreach ($result as $item) {
            /** @var Sitemap $item */
            /** @var PageTypeInterface $pageType */
            $pageType = $this->pageTypeSubManager->get($item->pageType());

            $pages = [];
            $pageResult = $this->pageRepository->findBy(['sitemapId' => (string)$item->id()]);
            foreach ($pageResult as $page) {
                /** @var Page $page */
                $pages[$page->locale()] = [
                    'page' => $page,
                    'url' => null,
                    'isOnline' => $page->isOnline(),
                ];
            }

            $items[] = [
                'sitemap' => $item,
                'pages' => $pages,
                'handle' => $item->handle(),
                'childrenAllowed' => !empty($pageType->allowedChildren()),
                'pageType' => [
                    'name' => $pageType->serviceName(),
                    'label' => $pageType->label(),
                    'allowedChildren' => [],
                    'isRoot' => $pageType instanceof RootPageTypeInterface,
                    'terminal' => $pageType instanceof TerminalPageTypeInterface,
                ],
                'children' => [],
            ];
        }

        return new ApiSuccessResponse([
            'items' => $items,
            'meta' => [
                'parentSitemapId' => $parentSitemap->id(),
                'count' => $count,
            ],
        ]);
    }
}