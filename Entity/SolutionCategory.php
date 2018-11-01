<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

/**
 * SolutionCategory
 */
class SolutionCategory
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $sortOrder;

    /**
     * @var string
     */
    private $sorting;

    /**
     * @var string
     */
    private $dateAdded;


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
     * Set name.
     *
     * @param string $name
     *
     * @return SolutionCategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return SolutionCategory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set sortOrder.
     *
     * @param int $sortOrder
     *
     * @return SolutionCategory
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder.
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set sorting.
     *
     * @param string $sorting
     *
     * @return SolutionCategory
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;

        return $this;
    }

    /**
     * Get sorting.
     *
     * @return string
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Set dateAdded.
     *
     * @param string $dateAdded
     *
     * @return SolutionCategory
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return string
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }
    /**
     * @var int|null
     */
    private $status = 0;

    /**
     * @var \DateTime
     */
    private $dateUpdated;


    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return SolutionCategory
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set dateUpdated.
     *
     * @param \DateTime $dateUpdated
     *
     * @return SolutionCategory
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated.
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }
}
