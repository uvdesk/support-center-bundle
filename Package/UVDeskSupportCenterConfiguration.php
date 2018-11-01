<?php

namespace Webkul\UVDesk\SupportCenterBundle\Package;

use Webkul\UVDesk\PackageManager\Extensions\HelpdeskExtension;
use Webkul\UVDesk\PackageManager\ExtensionOptions\HelpdeskExtension\Section as HelpdeskSection;

class UVDeskSupportCenterConfiguration extends HelpdeskExtension
{
    const FOLDERS_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M25.216,11.023h-14.4a4.708,4.708,0,0,0-4.777,4.624L6.011,43.394a4.729,4.729,0,0,0,4.8,4.625H49.223a4.729,4.729,0,0,0,4.8-4.625L54,21a5.234,5.234,0,0,0-5-5H30Z" />
SVG;

    const CATEGORIES_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M6,18h6V12l-6,.014V18Zm10-6v6H54V12H16ZM6,28h6V22l-6,.014V28Zm10-6v6H54V22H16ZM6,38h6V32l-6,.014V38Zm10-6v6H54V32H16ZM6,48h6V42l-6,.014V48Zm10-6v6H54V42H16Z" />
SVG;

    const ARTICLES_BRICK_SVG = <<<SVG
    <path fill-rule="evenodd" d="M34.743,5.977h-19a4.769,4.769,0,0,0-4.726,4.8L11,49.19a4.77,4.77,0,0,0,4.726,4.8h28.52a4.79,4.79,0,0,0,4.749-4.8V20.381ZM32,23V9L46,23H32Z" />
SVG;

    const BLOCK_SPAM_BRICK_SVG = <<<SVG
<path fill-rule="evenodd" d="M29.994,5.98A24.007,24.007,0,1,0,53.974,29.987,24,24,0,0,0,29.994,5.98ZM12,29.365A17.359,17.359,0,0,1,29.36,12a17.148,17.148,0,0,1,10.634,3.668L15.666,40A17.156,17.156,0,0,1,12,29.365ZM30.629,48a14.544,14.544,0,0,1-9.634-3.537L44.455,21a14.549,14.549,0,0,1,3.536,9.636A17.358,17.358,0,0,1,30.629,48Z" />
SVG;

    const KNOWLEDGEBASE_ICON_SVG = <<<SVG
<path fill-rule="evenodd" fill="rgb(158, 158, 158)" d="M14.000,0.000 L2.000,0.000 C0.969,0.000 0.000,0.901 0.000,2.000 L0.000,18.000 C0.000,19.099 0.969,20.000 2.000,20.000 L14.000,20.000 C15.031,20.000 16.000,19.099 16.000,18.000 L16.000,2.000 C16.000,0.901 15.031,0.000 14.000,0.000 ZM3.000,3.000 L9.000,3.000 L9.000,11.000 L6.000,9.000 L3.000,11.000 L3.000,3.000 Z" />
SVG;

    public function loadDashboardItems()
    {
        return [
            HelpdeskSection::KNOWLEDGEBASE => [
                [
                    'name' => 'Folders',
                    'route' => 'helpdesk_member_knowledgebase_folders_collection',
                    'brick_svg' => self::FOLDERS_BRICK_SVG,
                ],
                [
                    'name' => 'Categories',
                    'route' => 'helpdesk_member_knowledgebase_category_collection',
                    'brick_svg' => self::CATEGORIES_BRICK_SVG,
                ],
                [
                    'name' => 'Articles',
                    'route' => 'helpdesk_member_knowledgebase_article_collection',
                    'brick_svg' => self::ARTICLES_BRICK_SVG,
                ],
            ],
            HelpdeskSection::SETTINGS => [
                [
                    'name' => 'Block Spam',
                    'route' => 'helpdesk_member_knowledgebase_spam',
                    'brick_svg' => self::BLOCK_SPAM_BRICK_SVG,
                    'permission'=>'ROLE_ADMIN'
                ],
            ],
        ];
    }

    public function loadNavigationItems()
    {
        return [
            [
                'name' => 'Knowledgebase',
                'route' => 'helpdesk_member_knowledgebase_folders_collection',
                'icon_svg' => self::KNOWLEDGEBASE_ICON_SVG,
            ],
        ];
    }
}
