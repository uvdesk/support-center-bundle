<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ArticleFeedback
 */
class ArticleFeedback
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $isHelpful;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \Webkul\UVDesk\SupportCenterBundle\Entity\Article
     */
    private $article;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\User
     */
    private $ratedCustomer;


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
     * Set isHelpful
     *
     * @param boolean $isHelpful
     * @return ArticleFeedback
     */
    public function setIsHelpful($isHelpful)
    {
        $this->isHelpful = $isHelpful;

        return $this;
    }

    /**
     * Get isHelpful
     *
     * @return boolean 
     */
    public function getIsHelpful()
    {
        return $this->isHelpful;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ArticleFeedback
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ArticleFeedback
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set article
     *
     * @param \Webkul\UVDesk\SupportCenterBundle\Entity\Article $article
     * @return ArticleFeedback
     */
    public function setArticle(\Webkul\UVDesk\SupportCenterBundle\Entity\Article $article = null)
    {
        $this->article = $article;

        return $this;
    }

    /**
     * Get article
     *
     * @return \Webkul\UVDesk\SupportCenterBundle\Entity\Article 
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Set ratedCustomer
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\User $ratedCustomer
     * @return ArticleFeedback
     */
    public function setRatedCustomer(\Webkul\UVDesk\CoreBundle\Entity\User $ratedCustomer = null)
    {
        $this->ratedCustomer = $ratedCustomer;

        return $this;
    }

    /**
     * Get ratedCustomer
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\User 
     */
    public function getRatedCustomer()
    {
        return $this->ratedCustomer;
    }
}
