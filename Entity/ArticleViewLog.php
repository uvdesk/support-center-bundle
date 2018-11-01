<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ArticleViewLog
 */
class ArticleViewLog
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $viewedAt;

    /**
     * @var \Webkul\UserBundle\Entity\User
     */
    private $user;

    /**
     * @var \Webkul\UserBundle\Entity\Company
     */
    private $company;

    /**
     * @var \Webkul\SupportCenterBundle\Entity\Article
     */
    private $article;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set viewedAt
     *
     * @param \DateTime $viewedAt
     * @return ArticleViewLog
     */
    public function setViewedAt($viewedAt)
    {
        $this->viewedAt = $viewedAt;

        return $this;
    }

    /**
     * Get viewedAt
     *
     * @return \DateTime 
     */
    public function getViewedAt()
    {
        return $this->viewedAt;
    }

    /**
     * Set user
     *
     * @param \Webkul\UserBundle\Entity\User $user
     * @return ArticleViewLog
     */
    public function setUser(\Webkul\UVDesk\CoreBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

   

    /**
     * Set article
     *
     * @param \Webkul\SupportCenterBundle\Entity\Article $article
     * @return ArticleViewLog
     */
    public function setArticle(\Webkul\UVDesk\SupportCenterBundle\Entity\Article $article = null)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * Get article
     *
     * @return \Webkul\SupportCenterBundle\Entity\Article 
     */
    public function getArticle()
    {
        return $this->article;
    }
}
