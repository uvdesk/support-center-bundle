<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SolutionCategoryMapping
 */
class SolutionCategoryMapping
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $solutionId;

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
     * Set solutionId
     *
     * @param integer $solutionId
     * @return SolutionCategoryMapping
     */
    public function setSolutionId($solutionId)
    {
        $this->solutionId = $solutionId;

        return $this;
    }

    /**
     * Get solutionId
     *
     * @return integer 
     */
    public function getSolutionId()
    {
        return $this->solutionId;
    }

    /**
     * Set categoryId
     *
     * @param integer $categoryId
     * @return SolutionCategoryMapping
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
