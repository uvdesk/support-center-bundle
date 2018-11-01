<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

/**
 * Solutions
 */
class Solutions
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
     * @var string
     */
    private $visibility;

    /**
     * @var int
     */
    private $sortOrder;


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
     * @return Solutions
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
     * @return Solutions
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
     * Set visibility.
     *
     * @param string $visibility
     *
     * @return Solutions
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set sortOrder.
     *
     * @param int $sortOrder
     *
     * @return Solutions
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
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateUpdated;

    /**
     * @var string|null
     */
    private $solutionImage;


    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     *
     * @return Solutions
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set dateUpdated.
     *
     * @param \DateTime $dateUpdated
     *
     * @return Solutions
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

    /**
     * Set solutionImage.
     *
     * @param string|null $solutionImage
     *
     * @return Solutions
     */
    public function setSolutionImage($solutionImage = null)
    {
        $this->solutionImage = $solutionImage;

        return $this;
    }

    /**
     * Get solutionImage.
     *
     * @return string|null
     */
    public function getSolutionImage()
    {
        return $this->solutionImage;
    }
    //file upload   
    public function upload($file, $container)
    {   
        $container->get('file.service')->setRequiredFileSystem();
        $container->get('file.service')->upload($file);
    }

    public function removeUpload($file, $container)
    {   
        if($this->{$file} AND file_exists($this->{$file})){
            //call service
            $container->get('file.service')->removeUpload($file);
        }
    }
}
