<?php

namespace SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="SiteBundle\Repository\ArticleRepository")
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class Article
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="owner", referencedColumnName="id", onDelete="SET NULL")
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /** Photo
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="article_photo", fileNameProperty="photoName", size="photoSize")
     * 
     * @var File
     */
    private $photoFile;

    /** Photo
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $photoName;

    /** Photo
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var integer
     */
    private $photoSize;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /** Type de l'article
     * @ORM\ManyToOne(targetEntity="BuilderBundle\Entity\G_ListItem")
     * @ORM\JoinColumn(name="li_types_articles", referencedColumnName="id", nullable=false )
     */ //G_List: types_articles
     private $typeArticle; //Optionnel

    /**
     * Rights
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\Group")
     * @ORM\JoinColumn(name="resource_rights" )
     */
    private $rights;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @ORM\Column(name="publishedat", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $publishedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="isActive", type="boolean")
     */
    private $isActive;

    /** @ORM\OneToMany(targetEntity="SiteBundle\Entity\Comment", mappedBy="article", cascade={"persist"}, orphanRemoval=true) */
    private $comments;


    public function __construct()
    {
        $this->isActive = false;
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the value of author.
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Sets the value of author.
     *
     * @param User $author
     *
     * @return self
     */
    public function setAuthor(User $author)
    {
        // if ($author == null) {
        //     $this->author = null;
        // } else {
            $this->author = $author;
        // }

        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Title
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
     * Set content
     *
     * @param string $content
     *
     * @return Content
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set typeArticle
     *
     * @param \BuilderBundle\Entity\G_ListItem $type_article
     *
     * @return Article
     */
    public function setTypeArticle(\BuilderBundle\Entity\G_ListItem $typeArticle = null)
    {
        $this->typeArticle = $typeArticle;

        return $this;
    }

    /**
     * Get typeArticle
     *
     * @return \BuilderBundle\Entity\G_ListItem
     */
    public function getTypeArticle()
    {
        return $this->typeArticle;
    }

    /**
     * @return mixed
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * Set list rights
     *
     * @param \Doctrine\Common\Collections\Collection $rights
     *
     * @return List rights
     */
    public function setRights($rights)
    {
        $this->rights = $rights;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return P_Structure
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
     * @param integer $publishedAt
     *
     * @return Image
     */
    public function setPublishedAt($publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $image
     *
     * @return Article
     */
    public function setPhotoFile(File $image = null)
    {
        $this->photoFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getPhotoFile()
    {
        return $this->photoFile;
    }


    /**
     * Set photoName
     *
     * @param string $photoName
     *
     * @return Article
     */
    public function setPhotoName($photoName)
    {
        $this->photoName = $photoName;

        return $this;
    }

    /**
     * Get photoName
     *
     * @return string
     */
    public function getPhotoName()
    {
        return $this->photoName;
    }

    /**
     * Set photoSize
     *
     * @param integer $photoSize
     *
     * @return Article
     */
    public function setPhotoSize($photoSize)
    {
        $this->photoSize = $photoSize;

        return $this;
    }

    /**
     * Get photoSize
     *
     * @return integer
     */
    public function getPhotoSize()
    {
        return $this->photoSize;
    }

    /**
     * @param integer $updatedAt
     *
     * @return Image
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return integer|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updateDate()
    {
        $this->setUpdatedAt(new \Datetime());
    }

    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set menu pages
     *
     * @param string $comments
     *
     * @return Page Content
     */
    public function setComments($comments)
    {
        if (count($comments) > 0) {
            foreach ($comments as $i) {
                $this->addComment($i);
            }
        }

        return $this;
    }

    /**
     * Add comments
     *
     * @param \BuilderBundle\Entity\Menu_Page $comments
     *
     * @return Page
     */
    public function addComment(\BuilderBundle\Entity\Page_Content $comments)
    {
        $comments->setArticle($this);
        $this->comments[] = $comments;

        return $this;
    }

    /**
     * Remove comments
     *
     * @param \BuilderBundle\Entity\Page_Content $comments
     */
    public function removeComment(\BuilderBundle\Entity\Page_Content $comments)
    {
        $this->comments->removeElement($comments);
    }

}