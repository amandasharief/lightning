<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\DataMapper\DataMapper;

use Lightning\DataMapper\AbstractDataMapper;
use Lightning\Test\TestCase\DataMapper\Entity\Article;

class ArticleDataMapper extends AbstractDataMapper
{
    protected string|array $primaryKey = 'id';
    protected string $table = 'articles';
    protected array $fields = [
        'id', 'title','body','author_id','created_at','updated_at'
    ];

    public function createEntity(): Article
    {
        return new Article();
    }

    public function setProperty($property, $value)
    {
        $this->$property = $value;
    }

    public function getProperty($property)
    {
        return $this->$property;
    }

    protected array $called = [];
    protected ?string $stopOn = null;

    protected function wasCalled(string $method): void
    {
        $this->called[] = $method;
    }

    public function getCalled(): array
    {
        return $this->called;
    }

    public function stopOn(string $method): void
    {
        $this->stopOn = $method;
    }

    public function reset(): void
    {
        $this->called = [];
        $this->stopOn = null;
    }

    /**
     * Before create hook
     */
    protected function beforeCreate(object $entity): bool
    {
        parent::beforeCreate($entity);

        $this->wasCalled('beforeCreate');

        return $this->stopOn === 'beforeCreate' ? false : true;
    }

    /**
     * After create hook
     */
    protected function afterCreate(object $entity): void
    {
        parent::afterCreate($entity);

        $this->wasCalled('afterCreate');
    }

    /**
     * Before update hook
     */
    protected function beforeUpdate(object $entity): bool
    {
        parent::beforeUpdate($entity);

        $this->wasCalled('beforeUpdate');

        return $this->stopOn === 'beforeUpdate' ? false : true;
    }

    /**
     * after update hook
     */
    protected function afterUpdate(object $entity): void
    {
        parent::afterUpdate($entity);

        $this->wasCalled('afterUpdate');
    }

    /**
     * Before save hook
     */
    protected function beforeSave(object $entity): bool
    {
        parent::beforeSave($entity);

        $this->wasCalled('beforeSave');

        return $this->stopOn === 'beforeSave' ? false : true;
    }

    /**
     * After save hook
     */
    protected function afterSave(object $entity): void
    {
        parent::afterSave($entity);
        $this->wasCalled('afterSave');
    }

    /**
     * Before delete hook
     */
    protected function beforeDelete(object $entity): bool
    {
        parent::beforeDelete($entity);

        $this->wasCalled('beforeDelete');

        return $this->stopOn === 'beforeDelete' ? false : true;
    }

    /**
     * after delete hook
     */
    protected function afterDelete(object $entity): void
    {
        parent::afterDelete($entity); // code cover friendly
        $this->wasCalled('afterDelete');
    }

    /**
     * before find hook
     */
    protected function beforeFind(QueryObject $query): bool
    {
        parent::beforeFind($query);// code cover friendly
        $this->wasCalled('beforeFind');

        return $this->stopOn === 'beforeFind' ? false : true;
    }

    /**
     * After find hook
     */
    protected function afterFind(array $result, QueryObject $query): array
    {
        $result = parent::afterFind($result, $query); // code coverage friendly
        $this->wasCalled('afterFind');

        return $result;
    }

    public function callIsPersisted(object $entity): bool
    {
        return $this->isPersisted($entity);
    }

    public function callMarkIsPersisted(object $entity, bool $value): void
    {
        $this->markPersisted($entity, $value);
    }
}
