<?php

namespace Webkul\UVDesk\SupportCenterBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportGroup;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="Webkul\UVDesk\SupportCenterBundle\Repository\MarketingModuleRepository")
 */
class MarketingModule
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\SupportGroup")
     * @ORM\JoinColumn(nullable=false)
     */
    private $group;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", length=2000, nullable=true, name="description")
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true, length=255)
     */
    private $image;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $borderColor;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $linkURL;

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
     * Set title
     *
     * @param string $title
     *
     * @return MarketingModule
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return MarketingModule
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
     * Set image
     *
     * @param string $image
     *
     * @return MarketingModule
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return MarketingModule
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return MarketingModule
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return MarketingModule
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set group
     *
     * @return MarketingModule
     */
    public function setGroup(?SupportGroup $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return \Webkul\UserBundle\Entity\UserGroup
     */
    public function getGroup(): ?SupportGroup
    {
        return $this->group;
    }

    /**
     * Set borderColor
     *
     * @param string $borderColor
     *
     * @return MarketingModule
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;

        return $this;
    }

    /**
     * Get borderColor
     *
     * @return string
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    public function getHexToRGBAColor($alpha){
        $hex = str_replace('#', '', $this->getBorderColor());

        // Convert hex to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Return RGBA color
        return "rgba($r, $g, $b, $alpha)";
    }

    // public function setTmpStorageToImage($file = 'Image')
    // {
    //     $this->{'set'.$file}($this->{'tmp'.$file});
    // }

    /**
     * Set linkURL
     *
     * @param string $linkURL
     *
     * @return MarketingModule
     */
    public function setLinkURL($linkURL)
    {
        $this->linkURL = $linkURL;

        return $this;
    }

    /**
     * Get linkURL
     *
     * @return string
     */
    public function getLinkURL()
    {
        return $this->linkURL;
    }
}
