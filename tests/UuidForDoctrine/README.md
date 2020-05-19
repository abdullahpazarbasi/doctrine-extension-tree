# UUID Package for Doctrine

## Configuration

```yaml
# app/config/config.yml
doctrine:
    dbal:
        types:
            uuid: UuidForDoctrine\Doctrine\DBAL\Types\UuidType
```

or

```yaml
# app/config/config.yml
doctrine:
    dbal:
        connections:
            default: # or whatever
                mapping_types:
                    uuid: UuidForDoctrine\Doctrine\DBAL\Types\UuidType
```

## Usage

```php
namespace Modanisa;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="my_table")
 */
class MyEntity
{

    /**
     * @var UuidInterface|null
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="UuidForDoctrine\Doctrine\ORM\Id\UuidGenerator")
     */
    protected $id;

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

}

```

or

```php
namespace Modanisa;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="my_table")
 */
class MyEntity
{

    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="NONE")
     */
    protected $id;

    /**
     * @param UuidInterface $id
     */
    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

}

```
