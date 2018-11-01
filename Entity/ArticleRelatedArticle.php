<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ArticleRelatedArticle
 */
class ArticleRelatedArticle
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $companyId;

    /**
     * @var integer
     */
    private $articleId;

    /**
     * @var integer
     */
    private $relatedArticleId;


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
     * @return ArticleRelatedArticle
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
     * Set relatedArticleId
     *
     * @param integer $relatedArticleId
     * @return ArticleRelatedArticle
     */
    public function setRelatedArticleId($relatedArticleId)
    {
        $this->relatedArticleId = $relatedArticleId;

        return $this;
    }

    /**
     * Get relatedArticleId
     *
     * @return integer 
     */
    public function getRelatedArticleId()
    {
        return $this->relatedArticleId;
    }
}
