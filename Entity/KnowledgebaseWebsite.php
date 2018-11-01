<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

/**
 * KnowledgebaseWebsite
 */
class KnowledgebaseWebsite
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $brandColor;

    /**
     * @var string
     */
    private $pageBackgroundColor;

    /**
     * @var string
     */
    private $headerBackgroundColor;

    /**
     * @var string
     */
    private $linkColor;

    /**
     * @var string
     */
    private $articleTextColor;

    /**
     * @var string
     */
    private $ticketCreateOption;
     /**
     * @var string
     */
    private $siteDescription;

    /**
     * @var string
     */
    private $metaDescription;
    /**
     * @var string
     */
    private $metaKeywords;
    /**
     * @var string
     */
    private $homepageContent;
    /**
     * @var string
     */
    private $whiteList;

    /**
     * @var string
     */
    private $blackList;
    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var string
     */
    private $broadcastMessage;

    /**
     * @var bool
     */
    private $disableCustomerLogin;

    /**
     * @var string
     */
    private $script;

    /**
     * @var string
     */
    private $customCSS;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var array
     */
    private $headerLinks;

    /**
     * @var array
     */
    private $footerLinks;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->website = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return KnowledgebaseWebsite
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set brandColor.
     *
     * @param string $brandColor
     *
     * @return KnowledgebaseWebsite
     */
    public function setBrandColor($brandColor)
    {
        $this->brandColor = $brandColor;

        return $this;
    }

    /**
     * Get brandColor.
     *
     * @return string
     */
    public function getBrandColor()
    {
        return $this->brandColor;
    }

    /**
     * Set pageBackgroundColor.
     *
     * @param string $pageBackgroundColor
     *
     * @return KnowledgebaseWebsite
     */
    public function setPageBackgroundColor($pageBackgroundColor)
    {
        $this->pageBackgroundColor = $pageBackgroundColor;

        return $this;
    }

    /**
     * Get pageBackgroundColor.
     *
     * @return string
     */
    public function getPageBackgroundColor()
    {
        return $this->pageBackgroundColor;
    }

    /**
     * Set headerBackgroundColor.
     *
     * @param string $headerBackgroundColor
     *
     * @return KnowledgebaseWebsite
     */
    public function setHeaderBackgroundColor($headerBackgroundColor)
    {
        $this->headerBackgroundColor = $headerBackgroundColor;

        return $this;
    }
    /**
     * Get headerBackgroundColor.
     *
     * @return string
     */
    public function getHeaderBackgroundColor()
    {
        return $this->headerBackgroundColor;
    }

    /**
     * Set headerLinks
     *
     * @param array $headerLinks
     * @return Website
     */
    public function setHeaderLinks($headerLinks)
    {
        $this->headerLinks = $headerLinks;

        return $this;
    }

    /**
     * Get headerLinks
     *
     * @return array 
     */
    public function getHeaderLinks()
    {
        return $this->headerLinks;
    }

    /**
     * Set footerLinks
     *
     * @param array $footerLinks
     * @return Website
     */
    public function setFooterLinks($footerLinks)
    {
        $this->footerLinks = $footerLinks;

        return $this;
    }

    /**
     * Get footerLinks
     *
     * @return array 
     */
    public function getFooterLinks()
    {
        return $this->footerLinks;
    }
    /**
     * Set ticketCreateOption.
     *
     * @param string $ticketCreateOption
     *
     * @return KnowledgebaseWebsite
     */
    public function setTicketCreateOption($ticketCreateOption)
    {
        $this->ticketCreateOption = $ticketCreateOption;

        return $this;
    }

    /**
     * Get ticketCreateOption.
     *
     * @return string
     */
    public function getTicketCreateOption()
    {
        return $this->ticketCreateOption;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return KnowledgebaseWebsite
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return KnowledgebaseWebsite
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set broadcastMessage.
     *
     * @param string $broadcastMessage
     *
     * @return KnowledgebaseWebsite
     */
    public function setBroadcastMessage($broadcastMessage)
    {
        $this->broadcastMessage = $broadcastMessage;

        return $this;
    }

    /**
     * Get broadcastMessage.
     *
     * @return string
     */
    public function getBroadcastMessage()
    {
        return $this->broadcastMessage;
    }
    /**
     * @var \Webkul\UVDesk\SupportCenterBundle\Entity\website
     */
    private $website;
    /**
     * Set whiteList
     *
     * @param string $whiteList
     * @return null
     */
    public function setWhiteList($whiteList)
    {
        $this->whiteList = $whiteList;

        return $this;
    }

    /**
     * Get whiteList
     *
     * @return string 
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }
    /**
     * Set blackList
     *
     * @param string $blackList
     * @return 
     */
    public function setBlackList($blackList)
    {
        $this->blackList = $blackList;

        return $this;
    }

    /**
     * Get blackList
     *
     * @return string 
     */
    public function getBlackList()
    {
        return $this->blackList;
    }
    /**
     * Set website.
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\website|null $website
     *
     * @return KnowledgebaseWebsite
     */
    public function setWebsite(\Webkul\UVDesk\CoreBundle\Entity\Website $website = null)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website.
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\Website|null
     */
    public function getWebsite()
    {
        return $this->website;
    }
    
    /**
     * Set linkColor
     *
     * @param string $linkColor
     * @return Website
     */
    public function setLinkColor($linkColor)
    {
        $this->linkColor = $linkColor;

        return $this;
    }

    /**
     * Get linkColor
     *
     * @return string 
     */
    public function getLinkColor()
    {
        return $this->linkColor;
    }

    /**
     * Set articleTextColor
     *
     * @param string $articleTextColor
     * @return Website
     */
    public function setArticleTextColor($articleTextColor)
    {
        $this->articleTextColor = $articleTextColor;

        return $this;
    }

    /**
     * Get articleTextColor
     *
     * @return string 
     */
    public function getArticleTextColor()
    {
        return $this->articleTextColor;
    }

/**
     * @var string
     */
    private $bannerBackgroundColor;


    /**
     * Set bannerBackgroundColor
     *
     * @param string $bannerBackgroundColor
     * @return Website
     */
    public function setBannerBackgroundColor($bannerBackgroundColor)
    {
        $this->bannerBackgroundColor = $bannerBackgroundColor;

        return $this;
    }

    /**
     * Get bannerBackgroundColor
     *
     * @return string 
     */
    public function getBannerBackgroundColor()
    {
        return $this->bannerBackgroundColor;
    }

/**
     * @var string
     */
    private $linkHoverColor;


    /**
     * Set linkHoverColor
     *
     * @param string $linkHoverColor
     * @return Website
     */
    public function setLinkHoverColor($linkHoverColor)
    {
        $this->linkHoverColor = $linkHoverColor;

        return $this;
    }

    /**
     * Get linkHoverColor
     *
     * @return string 
     */
    public function getLinkHoverColor()
    {
        return $this->linkHoverColor;
    }
    /**
     * Set siteDescription
     *
     * @param string $siteDescription
     * @return Website
     */
    public function setSiteDescription($siteDescription)
    {
        $this->siteDescription = $siteDescription;

        return $this;
    }

    /**
     * Get siteDescription
     *
     * @return string 
     */
    public function getSiteDescription()
    {
        return $this->siteDescription;
    }

    /**
     * Set metaDescription
     *
     * @param string $metaDescription
     * @return Website
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    /**
     * Get metaDescription
     *
     * @return string 
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }
/**
     * Set homepageContent
     *
     * @param string $homepageContent
     * @return Website
     */
    public function setHomepageContent($homepageContent)
    {
        $this->homepageContent = $homepageContent;

        return $this;
    }

    /**
     * Get homepageContent
     *
     * @return string 
     */
    public function getHomepageContent()
    {
        return $this->homepageContent;
    }


/**
     * Set metaKeywords
     *
     * @param string $metaKeywords
     * @return Website
     */
    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    /**
     * Get metaKeywords
     *
     * @return string 
     */
    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    private $loginRequiredToCreate;


    /**
     * Set loginRequiredToCreate.
     *
     * @param bool $loginRequiredToCreate
     *
     * @return KnowledgebaseWebsite
     */
    public function setLoginRequiredToCreate($loginRequiredToCreate)
    {
        $this->loginRequiredToCreate = $loginRequiredToCreate;

        return $this;
    }

    /**
     * Get loginRequiredToCreate.
     *
     * @return bool
     */
    public function getLoginRequiredToCreate()
    {
        return $this->loginRequiredToCreate;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return bool
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set disableCustomerLogin
     *
     * @param boolean $disableCustomerLogin
     *
     * @return bool
     */
    public function setDisableCustomerLogin($disableCustomerLogin)
    {
        $this->disableCustomerLogin = $disableCustomerLogin;

        return $this;
    }

    /**
     * Get disableCustomerLogin
     *
     * @return bool
     */
    public function getDisableCustomerLogin()
    {
        return $this->disableCustomerLogin;
    }

     /**
     * Set script
     *
     * @param string $script
     * @return Website
     */
    public function setScript($script)
    {
        $this->script = $script;

        return $this;
    }

    /**
     * Get script
     *
     * @return string 
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     * Set customCSS
     *
     * @param string $customCSS
     * @return Website
     */
    public function setCustomCSS($customCSS)
    {
        $this->customCSS = $customCSS;

        return $this;
    }

    /**
     * Get customCSS
     *
     * @return string 
     */
    public function getCustomCSS()
    {
        return $this->customCSS;
    }

    /**
     * @var integer
     */
    private $removeCustomerLoginButton;

   
    /**
     * Set removeCustomerLoginButton
     *
     * @param integer $removeCustomerLoginButton
     * @return Website
     */
    public function setRemoveCustomerLoginButton($removeCustomerLoginButton)
    {
        $this->removeCustomerLoginButton = $removeCustomerLoginButton;

        return $this;
    }

    

    /**
     * Get removeCustomerLoginButton
     *
     * @return integer 
     */
    public function getRemoveCustomerLoginButton()
    {
        return $this->removeCustomerLoginButton;
    }

   
    

    /**
     * @var integer
     */
    private $removeBrandingContent;


    /**
     * Set removeBrandingContent
     *
     * @param integer $removeBrandingContent
     * @return Website
     */
    public function setRemoveBrandingContent($removeBrandingContent)
    {
        $this->removeBrandingContent = $removeBrandingContent;

        return $this;
    }

    /**
     * Get removeBrandingContent
     *
     * @return integer 
     */
    public function getRemoveBrandingContent()
    {
        return $this->removeBrandingContent;
    }

       
        
    

    
}
