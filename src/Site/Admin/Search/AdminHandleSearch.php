<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Cms\Site\Admin\Search;

use Ixocreate\Cms\Site\Admin\AdminItem;
use Ixocreate\Cms\Site\Admin\AdminSearchInterface;

final class AdminHandleSearch implements AdminSearchInterface
{
    /**
     * @param AdminItem $item
     * @param array $params
     * @return bool
     */
    public function search(AdminItem $item, array $params = []): bool
    {
        if (empty($params['handle'])) {
            return false;
        }

        return $item->handle() === $params['handle'];
    }
}