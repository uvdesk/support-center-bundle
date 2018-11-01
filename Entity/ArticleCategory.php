<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ArticleCategory
 */
class ArticleCategory
{
    /**
     * @var integer
     */
    private $id;

    // /**
    //  * @var integer
    //  */
    // private $companyId;

    /**
     * @var integer
     */
    private $articleId;

    /**
     * @var integer
     */
    private $categoryId;


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
     * Set articleId
     *
     * @param integer $articleId
     * @return ArticleCategory
     */
    public function setArticleId($articleId)
    {
        $this->articleId = $articleId;

        return $this;
    }

    /**
     * Get articleId
     *
     * @return integer 
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return ArticleCategory
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Get categoryId
     *
     * @return integer 
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }
}
