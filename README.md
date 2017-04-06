# Api-bundle

This bundle provides various tools to rapidly develop RESTful API's & applications with Symfony 3.x.

Features:
* Endpoint Generator (all you need is Entity)
* JSON response
* Exception hendler
* OAuth2 - friendsofsymfony/oauth-server-bundle

# Instalation
`composer require opstalent/api-bundle "~0.1`

#### Bundle Depend on
 
`"friendsofsymfony/oauth-server-bundle": "^1.5",
"friendsofsymfony/user-bundle": "~2.0@dev"`
# Manual Configuration
1. Create entity

```php
<?php

namespace AppBundle\Entity;

use AppBundle\Serializer\Annotation as AppSerializer;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * Class Page
 * @package AppBundle\Entity
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PageRepository")
 * @ORM\Table(name="pages")
 * @ORM\Table(indexes={@ORM\Index(name="slug_idx", columns={"slug"})})
 */
class Page
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 1,
     *      max = 128,
     *      minMessage = "Your Title must be at least {{ limit }} digits long",
     *      maxMessage = "Your Title cannot be longer than {{ limit }} digits"
     * )
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $title;

    /**
     * @var string
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(length=128, unique=true)
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $slug;
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $body;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $data;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $created;
    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"show", "update", "list"})
     */
    protected $updated;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Page
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated(): \DateTime
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     * @return Page
     */
    public function setCreated(\DateTime $created): Page
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     * @return Page
     */
    public function setUpdated(\DateTime $updated): Page
    {
        $this->updated = $updated;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Page
     */
    public function setTitle(string $title): Page
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return Page
     */
    public function setBody($body): Page
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }


}
```
2. Create Repository
Repository should extend `Opstalent\ApiBundle\Repository\BaseRepository`

```
<?php

namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Opstalent\ApiBundle\Repository\BaseRepository;

class PageRepository extends BaseRepository


{

 protected $filters = [];
 protected $repositoryName='AppBundle:Page';
 protected $repositoryAlias='page';
 protected $entityName='\AppBundle\Entity\Page';

}
```
3. Create Forms
Generator generate 3 forms `AddType`, `EditType` and `FilterType`
We recommend to separate add form and filter form but separate 

# Generator

# Recommended Security Bundle
[opstalent/security-bundle](https://github.com/opstalent/security-bundle)

# License

This bundle is under the MIT license. See the complete license in the bundle:

[License](LICENSE.md)