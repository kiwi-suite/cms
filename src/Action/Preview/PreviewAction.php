<?php
namespace KiwiSuite\Cms\Action\Preview;

use KiwiSuite\Admin\Entity\User;
use KiwiSuite\ApplicationHttp\Middleware\MiddlewareSubManager;
use KiwiSuite\Cms\Action\Frontend\RenderAction;
use KiwiSuite\Cms\Entity\Page;
use KiwiSuite\Cms\Entity\PageVersion;
use KiwiSuite\Cms\PageType\PageTypeInterface;
use KiwiSuite\Cms\PageType\PageTypeSubManager;
use KiwiSuite\Cms\Repository\PageVersionRepository;
use KiwiSuite\Cms\Request\CmsRequest;
use KiwiSuite\Cms\Site\Admin\Builder;
use KiwiSuite\Cms\Site\Admin\Item;
use KiwiSuite\CommonTypes\Entity\SchemaType;
use KiwiSuite\Entity\Type\Type;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\TextResponse;
use Zend\Expressive\MiddlewareContainer;
use Zend\Expressive\MiddlewareFactory;

final class PreviewAction implements MiddlewareInterface
{
    /**
     * @var Builder
     */
    private $builder;
    /**
     * @var PageVersionRepository
     */
    private $pageVersionRepository;
    /**
     * @var MiddlewareSubManager
     */
    private $middlewareSubManager;

    public function __construct(
        Builder $builder,
        PageVersionRepository $pageVersionRepository,
        MiddlewareSubManager $middlewareSubManager
    ) {
        $this->builder = $builder;
        $this->pageVersionRepository = $pageVersionRepository;
        $this->middlewareSubManager = $middlewareSubManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!array_key_exists('pageId', $request->getQueryParams())) {
            return new TextResponse("Invalid preview");
        }
        $pageId = $request->getQueryParams()['pageId'];

        $item = $this->builder->build()->findOneBy(function (Item $item) use ($pageId) {
            $pages = $item->pages();
            foreach ($pages as $pageItem) {
                if ((string) $pageItem['page']->id() === $pageId) {
                    return true;
                }
            }

            return false;
        });

        if (empty($item)) {
            return new TextResponse("Invalid preview");
        }

        $page = null;
        foreach ($item->pages() as $locale =>  $pageItem) {
            if ((string) $pageItem['page']->id() === $pageId) {
                $page = $pageItem['page'];
                break;
            }
        }

        if (empty($page)) {
            return new TextResponse("Invalid preview");
        }

        $pageVersion = $this->loadPageVersion($request, $page, $item->pageType());

        if (empty($pageVersion)) {
            return new TextResponse("Invalid preview");
        }

        $cmsRequest = (new CmsRequest($request))
            ->withSitemap($item->sitemap())
            ->withPage($page)
            ->withPageType($item->pageType())
            ->withPageVersion($pageVersion);

        $middleware = $item->pageType()->middleware();
        if (empty($middleware)) {
            $middleware = [];
        }
        $middleware[] = RenderAction::class;

        $middlewareFactory = new MiddlewareFactory(new MiddlewareContainer($this->middlewareSubManager));

        $pipe = $middlewareFactory->pipeline($middleware);
        return $pipe->handle($cmsRequest);
    }

    private function loadPageVersion(ServerRequestInterface $request, Page $page, PageTypeInterface $pageType): ?PageVersion
    {
        if (array_key_exists('versionId', $request->getQueryParams())) {
            return $this->pageVersionRepository->find($request->getQueryParams()['versionId']);
        }

        if ($request->getMethod() !== "POST") {
            return null;
        }

        $body = (string) $request->getBody();

        if (empty($body)) {
            return null;
        }

        $parsedBody = [];
        parse_str($body, $parsedBody);

        if (!array_key_exists('preview', $parsedBody) || empty($parsedBody['preview'])) {
            return null;
        }

        $json = $parsedBody['preview'];

        $parsedBody = \json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $content = [
            '__receiver__' => [
                'receiver' => PageTypeSubManager::class,
                'options' => [
                    'pageType' => $pageType::serviceName()
                ]
            ],
            '__value__' => $parsedBody,
        ];

        return new PageVersion([
            'id' => Uuid::uuid4()->toString(),
            'pageId' => $page->id(),
            'content' => Type::create($content, SchemaType::class)->convertToDatabaseValue(),
            'createdBy' => $request->getAttribute(User::class)->id(),
            'createdAt' => new \DateTimeImmutable(),
            'approvedAt' => null,

        ]);
    }
}