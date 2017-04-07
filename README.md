# Api-bundle [![Build Status](https://travis-ci.org/opstalent/api-bundle.svg?branch=master)](https://travis-ci.org/opstalent/api-bundle)

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
### 1. Create entity

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
### 2. Create Repository and register as service
Repository should extend `Opstalent\ApiBundle\Repository\BaseRepository`

```php
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
We need register repository as service
service.yml
````yml
parameters:
    entity.user: AppBundle\Entity\User
services:
    repository.user:
        class: AppBundle\Repository\UserRepository
        factory: ['@doctrine', getRepository]
        arguments: ['%entity.user%']
        calls: [[setEventDispatcher, ['@event_dispatcher']]]

````

### 3. Create Forms
Generator generate 3 forms `AddType`, `EditType` and `FilterType`
We recommend to separate add form and filter form but separate. 

#### AddType
````php
<?php

namespace AppBundle\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use AppBundle\Entity\Page;

class AddType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, ['required' => true, 'mapped' => true])
            ->add('slug', TextType::class, ['required' => true, 'mapped' => true])
            ->add('body', TextType::class, ['required' => false, 'mapped' => true])
            ->add('data', TextType::class, ['required' => false, 'mapped' => true])
            ->add('created', DateTimeType::class, ['required' => true, 'mapped' => true, 'widget' => 'single_text', 'format' => 'yyyy-MM-dd'])
            ->add('updated', DateTimeType::class, ['required' => true, 'mapped' => true, 'widget' => 'single_text', 'format' => 'yyyy-MM-dd']);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }


}
````
#### EditType
````php
<?php

namespace AppBundle\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->setMethod("PUT");
        /** @var FormBuilderInterface $field */
        foreach ($builder->all() as $field) {
            $field->setRequired(false);
        }
    }


}
````
#### FilterType
````php
<?php

namespace AppBundle\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

class FilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, ['required' => false, 'mapped' => false])
            ->add('slug', TextType::class, ['required' => false, 'mapped' => false])
            ->add('body', TextType::class, ['required' => false, 'mapped' => false])
            ->add('data', TextType::class, ['required' => false, 'mapped' => false])
            ->add('created', DateTimeType::class, ['required' => false, 'mapped' => false])
            ->add('updated', DateTimeType::class, ['required' => false, 'mapped' => false]);
    }

}
````
### 4. Create routing
We like to define routing for endpoint in separate file.

pages.yml
````yml
api_pages_list:
    path: /pages
    defaults: { _controller: 'OpstalentApiBundle:Action:list' }
    methods: [GET]
    options: { form: AppBundle\Form\Page\FilterType, serializerGroup: list, repository: '@repository.page', security: { secure: true, roles: [ROLE_SUPER_ADMIN] } }
api_pages_get:
    path: '/pages/{id}'
    requirements: { id: \d+ }
    defaults: { _controller: 'OpstalentApiBundle:Action:get' }
    methods: [GET]
    options: { serializerGroup: get, repository: '@repository.page', security: { secure: true, roles: [ROLE_SUPER_ADMIN] } }
api_pages_post:
    path: /pages
    defaults: { _controller: 'OpstalentApiBundle:Action:post' }
    methods: [POST]
    options: { form: AppBundle\Form\Page\AddType, serializerGroup: get, repository: '@repository.page', security: { secure: true, roles: [ROLE_SUPER_ADMIN] } }
api_pages_put:
    path: '/pages/{id}'
    requirements: { id: \d+ }
    defaults: { _controller: 'OpstalentApiBundle:Action:put' }
    methods: [PUT]
    options: { form: AppBundle\Form\Page\EditType, serializerGroup: get, repository: '@repository.page', security: { secure: true, roles: [ROLE_SUPER_ADMIN] } }
api_pages_delete:
    path: '/pages/{id}'
    requirements: { id: \d+ }
    defaults: { _controller: 'OpstalentApiBundle:Action:delete' }
    methods: [DELETE]
    options: { serializerGroup: get, repository: '@repository.page', security: { secure: true, roles: [ROLE_SUPER_ADMIN] } }
````
This file is used both by api-bundle and security-bundle. Let's take a closer look to this line
all endpoints use controller `OpstalentApiBundle:Action` and depend on action it will be `list, get, post, put, delete`.
parameter `methods: [GET]` is normal route parameter.
unde option we have some custom params.
##### form
Define what form type should be used for example `AppBundle\Form\Page\FilterType`
##### serializerGroups(optional)
Define what serializer group should be used on different roles. Default is `list` for List action and `get` for all other action. If we want custom serializedGroups on this action we can define own table for example
````yml
serializerGroups:
    all: "list"
    owner: "me"
    ROLE_SUPER_ADMIN: 'me'
````
All option is used when action cannot match any other option and its mandatory if we define parameter `serializerGroups`. Option owner is used to check is logged user is owner of this data and could be defined for all actions except list.
Third value is role, and if user has this role (or more) he get all serializer Groups he match.

##### repository
is used to define what repository should be used for this action for example `'@repository.page'`
##### security
Security define how we want to secure our endpoint. For all actions default `secure` option is `true` and `roles` is `ROLE_SUPER_ADMIN` .
There is third option where we define events for this endpoint.
Full security example
````yml
 security:
    secure: true
    roles: [ROLE_USER]
    events:
        before.persist: 'owner'
````
# Generator
Generator is used to generate all files described above for us. All we need is Entity.
To run generator we need type

`php bin/console app:generatecrude` 

additional options
* entityPath - to generate crud for specific entity
* overwrite - to overwrite previous configuration
* actions to define what actions shoudl be generated for endpoints. Available actions `[LIST,GET,POST,PUT,DELETE]`

# Recommended Security Bundle
[opstalent/security-bundle](https://github.com/opstalent/security-bundle)

# License

This bundle is under the MIT license. See the complete license in the bundle:

[License](LICENSE.md)