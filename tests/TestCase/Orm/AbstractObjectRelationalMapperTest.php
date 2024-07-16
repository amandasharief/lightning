<?php declare(strict_types=1);

namespace Lightning\Test\Orm;

use PDO;
use LogicException;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;

use function Lightning\Dotenv\env;

use Lightning\Orm\DataMapperFactory;
use Lightning\Orm\DataMapperManager;
use Lightning\Test\Entity\TagEntity;
use Lightning\Fixture\FixtureManager;
use Lightning\Test\Entity\PostEntity;
use Lightning\Test\Entity\UserEntity;
use Lightning\Test\Entity\AuthorEntity;
use Lightning\Test\Fixture\TagsFixture;
use Lightning\EventManager\EventManager;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Test\Entity\ArticleEntity;
use Lightning\Test\Entity\ProfileEntity;
use Lightning\Test\Fixture\PostsFixture;
use Lightning\Test\Fixture\UsersFixture;
use Lightning\Test\PersistentPdoFactory;
use Lightning\Test\Fixture\AuthorsFixture;
use Lightning\Test\Fixture\ArticlesFixture;
use Lightning\Test\Fixture\ProfilesFixture;
use Lightning\Test\Fixture\PostsTagsFixture;
use Lightning\EventManager\EventManagerInterface;
use Lightning\Orm\AbstractObjectRelationalMapper;
use Lightning\DataMapper\DataSource\DatabaseDataSource;

abstract class MockMapper extends AbstractObjectRelationalMapper
{
    public function checkAssociationDefinition(string $assoc, array $config): void
    {
        $this->validateAssociationDefinition($assoc, $config);
    }

    public function setAssociation(string $name, array $setting): self
    {
        $this->$name = $setting;

        return $this;
    }

    public function setConditions(string $association, string $name, array $conditions)
    {
        $this->$association[$this->findIndex($association, $name)]['conditions'] = $conditions;
    }

    public function setOrder(string $association, string $name, $order)
    {
        $this->$association[$this->findIndex($association, $name)]['order'] = $order;
    }

    private function findIndex(string $association, string $name): ?int
    {
        foreach ($this->$association as $key => $config) {
            if ($config['propertyName'] === $name) {
                return $key;
            }
        }

        return null;
    }
}

class Article extends MockMapper
{
    protected string|array $primaryKey = 'id';

    protected string $table = 'articles';

    protected array $fields = [
        'id', 'title','body','author_id','created_at','updated_at'
    ];

    protected array $belongsTo = [
        [
            'className' => Author::class,
            'foreignKey' => 'author_id',
            'propertyName' => 'author'
        ]
    ];

    public function createEntity(): ArticleEntity
    {
        return new ArticleEntity();
    }
}

class Author extends MockMapper
{
    protected string $table = 'authors';

    protected array $fields = [
        'id', 'name', 'created_at','updated_at'
    ];

    protected array $hasMany = [
        [
            'className' => Article::class,
            'foreignKey' => 'author_id', // in other table,
            'dependent' => true,
            'propertyName' => 'articles'
        ]
    ];

    public function setDependent(bool $dependent): void
    {
        $this->hasMany[0]['dependent'] = $dependent;
    }

    public function createEntity(): AuthorEntity
    {
        return new AuthorEntity();
    }
}

class Profile extends MockMapper
{
    protected string $table = 'profiles';

    protected array $fields = [
        'id', 'name','user_id','created_at','updated_at'
    ];

    protected array $belongsTo = [
        [
            'className' => User::class,
            'foreignKey' => 'user_id',
            'propertyName' => 'user'
        ]
    ];

    public function createEntity(): object
    {
        return new ProfileEntity();
    }
}

class User extends MockMapper
{
    protected string $table = 'users';
    protected array $fields = [
        'id', 'name','created_at','updated_at'
    ];
    protected array $hasOne = [
        [
            'className' => Profile::class,
            'foreignKey' => 'user_id', // other table
            'dependent' => true,
            'propertyName' => 'profile'
        ]
    ];

    public function setDependent(bool $dependent): void
    {
        $this->hasOne[0]['dependent'] = $dependent;
    }

    public function createEntity(): object
    {
        return new UserEntity();
    }
}

class Tag extends MockMapper
{
    protected string $table = 'tags';
    protected array $fields = [
        'id', 'name','created_at','updated_at'
    ];
    public function createEntity(): object
    {
        return new TagEntity();
    }
}

class Post extends MockMapper
{
    protected string $table = 'posts';
    protected array $fields = [
        'id', 'title', 'body','created_at','updated_at'
    ];
    protected array $belongsToMany = [
        [
            'className' => Tag::class,
            'joinTable' => 'posts_tags',
            'foreignKey' => 'post_id',
            'otherForeignKey' => 'tag_id',
            'propertyName' => 'tags'
        ]
    ];

    public function setDependent(bool $dependent): void
    {
        $this->belongsToMany[0]['dependent'] = $dependent;
    }

    public function createEntity(): object
    {
        return new PostEntity();
    }
}

/**
 * TODO: Tests that are modifying data in SQLITE is causing tests to fail
 */
final class AbstractObjectRelationalMapperTest extends TestCase
{
    protected ?PDO $pdo;
    protected FixtureManager $fixtureManager;
    protected DatabaseDataSource $dataSource;
    protected Hydrator $hydrator;
    protected EventManagerInterface $eventManager;
    protected DataMapperManager $mapperManager;

    public function setUp(): void
    {
        $this->pdo = (new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->dataSource = new DatabaseDataSource($this->pdo, new QueryBuilder());
        $this->hydrator = new Hydrator();
        $this->eventManager = new EventManager();
        $this->mapperManager = new DataMapperManager(new DataMapperFactory($this->dataSource, $this->hydrator, $this->eventManager));

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([
            ArticlesFixture::class,
            AuthorsFixture::class,
            UsersFixture::class,
            TagsFixture::class,
            ProfilesFixture::class,
            PostsFixture::class,
            PostsTagsFixture::class,
        ]);
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    public function testBelongsTo(): void
    {
        $article = new Article($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $article->find(['id' => 1000], ['with' => ['author']]);

        # Important check with array not toJson
        $expected = [
            'id' => 1000,
            'title' => 'Article #1',
            'body' => 'A description for article #1',
            'author_id' => 2000,
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'author' => [
                'id' => 2000,
                'name' => 'Jon',
                'created_at' => '2021-10-03 14:01:00',
                'updated_at' => '2021-10-03 14:02:00'
            ]
        ];

        $this->assertInstanceOf(ArticleEntity::class, $result);
        $this->assertInstanceOf(AuthorEntity::class, $result->getAuthor());

        $this->assertEquals($expected, $result->toState());
    }

    public function testBelongsToConditions(): void
    {
        $article = new Article($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $article->setAssociation('belongsTo', [
            [
                'className' => Author::class,
                'foreignKey' => 'author_id',
                'order' => null,
                'conditions' => [
                    'authors.id <>' => 2000
                ],
                'propertyName' => 'author'
            ]

        ]);
        $result = $article->find(['id' => 1000], ['with' => ['author']]);

        # Important check with array not toJson
        $expected = [
            'id' => 1000,
            'title' => 'Article #1',
            'body' => 'A description for article #1',
            'author_id' => 2000,
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'author' => null

        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testBelongsToNotFound(): void
    {
        $this->dataSource->delete('authors', []);

        $article = new Article($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $article->find(['id' => 1000], ['with' => ['author']]);
        $expected = [
            'id' => 1000,
            'title' => 'Article #1',
            'body' => 'A description for article #1',
            'author_id' => 2000,
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'author' => null
        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testHasOne(): void
    {
        $user = new User($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $user->find(['id' => 1000], ['with' => ['profile']]);

        # Important check with array not toJson
        $expected = [
            'id' => 1000,
            'name' => 'User #1',
            'created_at' => '2021-10-14 09:01:00',
            'updated_at' => '2021-10-14 09:02:00',
            'profile' => [
                'id' => 2000,
                'name' => 'admin',
                'user_id' => 1000,
                'created_at' => '2021-10-03 14:01:00',
                'updated_at' => '2021-10-03 14:02:00'
            ]
        ];

        $this->assertEquals($expected, $result->toState());
    }

    /**
     * TOOD: rewrite test so its not modifying db since this causes for random errors in the CI matrix with other SQLITE and PHP versions
     */
    public function testHasOneConditions(): void
    {
        // Create Extra Record
        $profile = new Profile($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $result = $profile->getDataSource()->update('profiles', ['user_id' => 1000]);

        $user = new User($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $user->setAssociation('hasOne', [
            [
                'className' => Profile::class,
                'foreignKey' => 'user_id', // other table
                'dependent' => true,
                'propertyName' => 'profile',
                'conditions' => [
                    'profiles.id <>' => 2000
                ],
                'order' => null,
                'propertyName' => 'profile'

            ]
        ]);

        $result = $user->find(['id' => 1000], ['with' => ['profile']]);

        # Important check with array not toJson
        $expected = [
            'id' => 1000,
            'name' => 'User #1',
            'created_at' => '2021-10-14 09:01:00',
            'updated_at' => '2021-10-14 09:02:00',
            'profile' => [
                'id' => 2001,
                'name' => 'standard',
                'user_id' => 1000,
                'created_at' => '2021-10-03 14:03:00',
                'updated_at' => '2021-10-03 14:04:00'
            ]
        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testHasOneNotFound(): void
    {
        $this->dataSource->delete('profiles', []);
        $user = new User($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $user->find(['id' => 1000], ['with' => ['profile']]);

        # Important check with array not toJson
        $expected = [
            'id' => 1000,
            'name' => 'User #1',
            'created_at' => '2021-10-14 09:01:00',
            'updated_at' => '2021-10-14 09:02:00',
            'profile' => null
        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testHasOneDepenent(): void
    {
        $user = new User($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $user->setDependent(true);

        $query = ['criteria' => ['user_id' => 1000]];

        $this->assertEquals(1, $this->dataSource->count('profiles', $query));
        $this->assertEquals(1, $user->delete($user->get(1000)));
        $this->assertEquals(0, $this->dataSource->count('profiles', $query));
    }

    public function testHasMany(): void
    {
        $this->dataSource->update('articles', ['author_id' => 2000], ['criteria' => ['id' => 1002]]);

        $author = new Author($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $author->find(['id' => 2000], ['with' => ['articles']]);

        $expected = [
            'id' => 2000,
            'name' => 'Jon',
            'created_at' => '2021-10-03 14:01:00',
            'updated_at' => '2021-10-03 14:02:00',
            'articles' => [
                0 => [
                    'id' => 1000,
                    'title' => 'Article #1',
                    'body' => 'A description for article #1',
                    'author_id' => 2000,
                    'created_at' => '2021-10-03 09:01:00',
                    'updated_at' => '2021-10-03 09:02:00'
                ],
                1 => [
                    'id' => 1002,
                    'title' => 'Article #3',
                    'body' => 'A description for article #3',
                    'author_id' => 2000,
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00'
                ]
            ]
        ];

        $this->assertEquals($expected, $result->toState());
    }

    /**
     * TOOD: rewrite test so its not modifying db since this causes for random errors in the CI matrix with other SQLITE and PHP versions
     */
    public function testHasManyConditions(): void
    {
        $this->dataSource->update('articles', ['author_id' => 2000], ['criteria' => ['id' => 1002]]);

        $author = new Author($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $author->setAssociation('hasMany', [
            [
                'className' => Article::class,
                'foreignKey' => 'author_id', // in other table,
                'dependent' => true,
                'conditions' => ['id <>' => 1000],
                'order' => null,
                'propertyName' => 'articles'
            ]
        ]);

        $result = $author->find(['id' => 2000], ['with' => ['articles']]);

        $expected = [
            'id' => 2000,
            'name' => 'Jon',
            'created_at' => '2021-10-03 14:01:00',
            'updated_at' => '2021-10-03 14:02:00',
            'articles' => [
                0 => [
                    'id' => 1002,
                    'title' => 'Article #3',
                    'body' => 'A description for article #3',
                    'author_id' => 2000,
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00'
                ]
            ]
        ];

        $this->assertEquals($expected, $result->toState());
    }

    public function testHasManyOrder(): void
    {
        $this->dataSource->update('articles', ['author_id' => 2000], ['criteria' => ['id' => 1002]]);

        $author = new Author($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $author->setOrder('hasMany', 'articles', 'id DESC');

        $result = $author->find(['id' => 2000], ['with' => ['articles']]);

        $expected = [
            'id' => 2000,
            'name' => 'Jon',
            'created_at' => '2021-10-03 14:01:00',
            'updated_at' => '2021-10-03 14:02:00',
            'articles' => [
                0 => [
                    'id' => 1002,
                    'title' => 'Article #3',
                    'body' => 'A description for article #3',
                    'author_id' => 2000,
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00',
                ],
                1 => [
                    'id' => 1000,
                    'title' => 'Article #1',
                    'body' => 'A description for article #1',
                    'author_id' => 2000,
                    'created_at' => '2021-10-03 09:01:00',
                    'updated_at' => '2021-10-03 09:02:00'
                ]
            ]
        ];

        $this->assertEquals($expected, $result->toState());
    }

    public function testHasManyDependent(): void
    {
        $author = new Author($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $author->setDependent(true);

        $query = ['criteria' => ['author_id' => 2000]];

        $this->assertEquals(1, $this->dataSource->count('articles', $query));
        $this->assertEquals(1, $author ->delete($author->get(2000)));
        $this->assertEquals(0, $this->dataSource->count('articles', $query));
    }

    public function testHasManyNotFound(): void
    {
        $this->dataSource->delete('articles', []);

        $author = new Author($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $result = $author->find(['id' => 2000], ['with' => ['articles']]);

        $expected = [
            'id' => 2000,
            'name' => 'Jon',
            'created_at' => '2021-10-03 14:01:00',
            'updated_at' => '2021-10-03 14:02:00',
            'articles' => []
        ];

        $this->assertEquals($expected, $result->toState());
    }

    public function testBelongsToMany(): void
    {
        $this->dataSource->update('posts_tags', ['post_id' => 1000], ['criteria' => ['post_id' => 1002]]);

        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $result = $post->find(['id' => 1000], ['with' => ['tags']]);

        $expected = [
            'id' => 1000,
            'title' => 'Post #1',
            'body' => 'A description for post #1',
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'tags' => [
                0 => [
                    'id' => 2000,
                    'name' => 'Tag #1',
                    'created_at' => '2021-10-03 09:01:00',
                    'updated_at' => '2021-10-03 09:02:00',
                ],
                1 => [
                    'id' => 2002,
                    'name' => 'Tag #3',
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00'
                ]
            ]
        ];
        $this->assertEquals($expected, $result->toState());
    }

    /**
     * TOOD: rewrite test so its not modifying db since this causes for random errors in the CI matrix with other SQLITE and PHP versions
     */
    public function testBelongsToManyConditions(): void
    {
        // Create extra
        $this->dataSource->update('posts_tags', ['post_id' => 1000], ['criteria' => ['post_id' => 1002]]);

        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $post->setAssociation('belongsToMany', [
            [
                'className' => Tag::class,
                'joinTable' => 'posts_tags',
                'foreignKey' => 'post_id',
                'otherForeignKey' => 'tag_id',
                'conditions' => [
                    'id !=' => 2000,
                ],
                'order' => null,
                'propertyName' => 'tags'
            ]
        ]);

        $result = $post->find(['id' => 1000], ['with' => ['tags']]);

        $expected = [
            'id' => 1000,
            'title' => 'Post #1',
            'body' => 'A description for post #1',
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'tags' => [
                0 => [
                    'id' => 2002,
                    'name' => 'Tag #3',
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00'
                ]
            ]
        ];
        $this->assertEquals($expected, $result->toState());
    }

    /**
     * TODO: This not passing on github actions
     */
    public function testBelongsToManyOrder(): void
    {
        // Create extra
        $this->dataSource->update('posts_tags', ['post_id' => 1000], ['criteria' => ['post_id' => 1002]]);

        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $post->setAssociation('belongsToMany', [
            [
                'className' => Tag::class,
                'joinTable' => 'posts_tags',
                'foreignKey' => 'post_id',
                'otherForeignKey' => 'tag_id',
                'order' => 'id DESC',
                'conditions' => [],
                'propertyName' => 'tags'
            ]]);

        $result = $post->find(['id' => 1000], ['with' => ['tags']]);

        $expected = [
            'id' => 1000,
            'title' => 'Post #1',
            'body' => 'A description for post #1',
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'tags' => [
                0 => [
                    'id' => 2002,
                    'name' => 'Tag #3',
                    'created_at' => '2021-10-03 09:05:00',
                    'updated_at' => '2021-10-03 09:06:00'
                ],
                1 => [
                    'id' => 2000,
                    'name' => 'Tag #1',
                    'created_at' => '2021-10-03 09:01:00',
                    'updated_at' => '2021-10-03 09:02:00',
                ]
            ]
        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testBelongsToManyNotFound(): void
    {
        $this->dataSource->delete('tags', []);

        // Create extra
        $this->dataSource->update('posts_tags', ['post_id' => 1000], ['criteria' => ['post_id' => 1002]]);

        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $result = $post->find(['id' => 1000], ['with' => ['tags']]);

        $expected = [
            'id' => 1000,
            'title' => 'Post #1',
            'body' => 'A description for post #1',
            'created_at' => '2021-10-03 09:01:00',
            'updated_at' => '2021-10-03 09:02:00',
            'tags' => []
        ];
        $this->assertEquals($expected, $result->toState());
    }

    public function testHasAndBelongsToDependent(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);
        $post->setDependent(true);

        $query = ['criteria' => ['post_id' => 1000]];

        $this->assertEquals(2, $this->dataSource->count('posts_tags', $query));
        $this->assertEquals(1, $post ->delete($post->get(1000)));
        $this->assertEquals(0, $this->dataSource->count('posts_tags', $query));
    }

    public function testInvalidAssociationDefinitionPropertyName(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('belongsTo is missing propertyName');

        $post->checkAssociationDefinition('belongsTo', [
            'className' => Profile::class,
            'foreignKey' => 'user_id', // other table
            'dependent' => true,
            'conditions' => [],
            'order' => null,
        ]);
    }

    public function testInvalidAssociationDefinitionForeignKey(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('belongsTo `foo` is missing foreignKey');

        $post->checkAssociationDefinition('belongsTo', [
            'className' => Profile::class,
            'dependent' => true,
            'propertyName' => 'foo',
            'conditions' => [],
            'order' => null,
        ]);
    }

    public function testInvalidAssociationDefinitionClassName(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('belongsTo `foo` is missing className');

        $post->checkAssociationDefinition('belongsTo', [
            // 'className' => Profile::class,
            'foreignKey' => 'user_id', // other table
            'dependent' => true,
            'propertyName' => 'foo',
            'conditions' => [],
            'order' => null,
        ]);
    }

    public function testInvalidAssociationDefinitionJoinTable(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('belongsToMany `tags` is missing joinTable');

        $post->checkAssociationDefinition('belongsToMany', [
            'className' => Tag::class,
            'foreignKey' => 'post_id',
            'otherForeignKey' => 'tag_id',
            'conditions' => [],
            'order' => null,
            'propertyName' => 'tags'
        ]);
    }

    public function testInvalidAssociationDefinitionOtherForeignKey(): void
    {
        $post = new Post($this->dataSource, $this->hydrator, $this->eventManager, $this->mapperManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('belongsToMany `tags` is missing otherForeignKey');

        $post->checkAssociationDefinition('belongsToMany', [
            'className' => Tag::class,
            'joinTable' => 'posts_tags',
            'foreignKey' => 'post_id',
            'conditions' => [],
            'order' => null,
            'propertyName' => 'tags'
        ]);
    }
}
