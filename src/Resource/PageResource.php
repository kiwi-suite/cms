<?php

namespace KiwiSuite\Cms\Resource;

use KiwiSuite\Admin\Resource\ResourceInterface;
use KiwiSuite\Admin\Resource\ResourceTrait;
use KiwiSuite\Cms\Action\Page\IndexAction;
use KiwiSuite\Cms\Message\CreatePage;
use KiwiSuite\Cms\Repository\PageRepository;

final class PageResource implements ResourceInterface
{
    use ResourceTrait;

    public function createMessage(): string
    {
        return CreatePage::class;
    }

    public function indexAction(): ?string
    {
        return IndexAction::class;
    }

    public static function name(): string
    {
        return "page";
    }

    public function repository(): string
    {
        return PageRepository::class;
    }

    public function icon(): string
    {
        return "fa";
    }

    public function schema(): array
    {
        return [
            'name'       => 'Page',
            'namePlural' => 'Pages',
            'form'       => [
                [
                    'wrappers'        => [
                        'section',
                    ],
                    'templateOptions' => [
                        'label' => 'General',
                        'icon'  => 'fa fa-cog',
                    ],
                    'fieldGroup'      => [
                        [
                            'key'             => 'name',
                            'type'            => 'input',
                            'templateOptions' => [
                                'label'       => 'Name',
                                'placeholder' => 'Name',
                                'required'    => true,
                            ],
                        ],
                    ],
                ],
                [
                    'wrappers'        => [
                        'section',
                    ],
                    'templateOptions' => [
                        'label' => 'Scheduling',
                        'icon'  => 'fa fw-fw fa-calendar',
                    ],
                    'fieldGroup'      => [
                        [
                            'key'             => 'publishedFrom',
                            'type'            => 'datetime',
                            'templateOptions' => [
                                'label'       => 'Published Until',
                                'placeholder' => 'Published Until',
                                'config'      => [
                                    'dateInputFormat' => 'YYYY-MM-DD HH:mm:ss',
                                ],
                            ],
                        ],
                        [
                            'key'             => 'publishedUntil',
                            'type'            => 'datetime',
                            'templateOptions' => [
                                'label'       => 'Published Until',
                                'placeholder' => 'Published Until',
                                'config'      => [
                                    'dateInputFormat' => 'YYYY-MM-DD HH:mm:ss',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'wrappers'        => [
                        'section',
                    ],
                    'templateOptions' => [
                        'label' => 'Status',
                        'icon'  => 'fa fw-fw fa-power-off',
                    ],
                    'fieldGroup'      => [
                        [
                            'key'             => 'status',
                            'type'            => 'select',
                            'defaultValue'    => 'active',
                            'templateOptions' => [
                                'label'       => 'Status',
                                'placeholder' => 'Status',
                                'required'    => true,
                                'options'     => [
                                    [
                                        'label' => 'Online',
                                        'value' => 'online',
                                    ],
                                    [
                                        'label' => 'Offline',
                                        'value' => 'offline',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
