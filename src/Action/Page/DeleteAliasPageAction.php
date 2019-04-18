<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Package\Cms\Action\Page;

use Ixocreate\Package\Admin\Response\ApiErrorResponse;
use Ixocreate\Package\Admin\Response\ApiSuccessResponse;
use Ixocreate\Package\Cms\Repository\OldRedirectRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DeleteAliasPageAction implements MiddlewareInterface
{
    /**
     * @var OldRedirectRepository
     */
    private $oldRedirectRepository;

    public function __construct(OldRedirectRepository $oldRedirectRepository)
    {
        $this->oldRedirectRepository = $oldRedirectRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $slug = $request->getParsedBody();
        if (!\is_string($slug['url'])) {
            return new ApiErrorResponse("Invalid Url");
        }

        $pageVersion = $this->oldRedirectRepository->findOneBy(['oldUrl' => $slug['url']]);
        $this->oldRedirectRepository->remove($pageVersion);

        return new ApiSuccessResponse();
    }
}
