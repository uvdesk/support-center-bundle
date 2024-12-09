<?php

namespace Webkul\UVDesk\SupportCenterBundle\UIComponents\Dashboard\Panel\Items\Knowledgebase;

use Webkul\UVDesk\CoreFrameworkBundle\Dashboard\Segments\PanelSidebarItemInterface;
use Webkul\UVDesk\SupportCenterBundle\UIComponents\Dashboard\Panel\Sidebars\Knowledgebase;

class MarketingModule implements PanelSidebarItemInterface
{
    public static function getTitle() : string
    {
        return "Marketing Modules";
    }

    public static function getRouteName() : string
    {
        return 'helpdesk_member_knowledgebase_marketing_module';
    }

    public static function getSupportedRoutes() : array
    {
        return [
            'helpdesk_member_knowledgebase_create_marketing_module',
            'helpdesk_member_knowledgebase_update_marketing_module', 
            'helpdesk_member_knowledgebase_marketing_module',
        ];
    }

    public static function getRoles() : array
    {
        return ['ROLE_AGENT_MANAGE_KNOWLEDGEBASE'];
    }

    public static function getSidebarReferenceId() : string
    {
        return Knowledgebase::class;
    }
}
