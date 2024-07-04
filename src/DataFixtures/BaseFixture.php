<?php
declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use LogicException;
use function Symfony\Component\String\u;

abstract class BaseFixture extends Fixture
{
    protected Generator $faker;
    protected ObjectManager $entityManager;

    abstract protected function loadData(): void;

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {}

    final public function load(ObjectManager $manager): void
    {
        $this->entityManager = $this->managerRegistry->getManager($this->getTargetEntityManagerName());
        $this->faker = Factory::create('fr_FR');

        $this->loadData();

        $this->entityManager->flush();
    }

    /**
     * Create many objects at once:
     *
     *      $this->createMany(10, function(int $i) {
     *          $user = new User();
     *          $user->setFirstName('Ryan');
     *
     *           return $user;
     *      });
     *
     * @template T of object
     * @param int      $count
     * @param class-string<T>   $class fixture class to create
     * @param callable $factory
     */
    protected function createMany(int $count, string $class, callable $factory, ?string $suffix = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createOne($class, $factory, $suffix);
        }
    }

    /**
     * Create one object
     * Object reference name will be suffixed by the number of corresponding $class already registered

     * @template T of object
     * @param class-string<T> $class fixture class
     * @param callable $factory callback method after each object creation with count of current references as parameter
     * @return T
     */
    protected function createOne(string $class, callable $factory, ?string $suffix = null): mixed
    {
        // get the number of entity that have already been created
        $count = $this->_getNumberOfReferenceByClass($class, $suffix);

        $entity = $factory($count);

        if (null === $entity) {
            throw new LogicException('Did you forget to return the entity object from your callback to BaseFixture::createMany()?');
        }

        $this->entityManager->persist($entity);

        $referenceName = $this->_getReferenceName($class, $count, $suffix);

        $this->addReference($referenceName, $entity);

        return $entity;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return array<string, T>
     */
    protected function getAllReferences(string $class, ?string $suffix = null): array
    {
        $referencesByClass = $this->referenceRepository->getReferencesByClass();
        if (array_key_exists($class, $referencesByClass))
        {
            /** @var array<string, T> $classReferences */
            $classReferences = $referencesByClass[$class];
            if ($suffix === null)
                return $classReferences;
            else
            {
                return array_filter($classReferences, fn(string $key) => str_contains($key, $suffix), ARRAY_FILTER_USE_KEY);
            }
        }
        return [];
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    protected function getRandomReference(string $class, ?string $suffix = null): ?object
    {
        $references = $this->getAllReferences($class, $suffix);
        $randomReferenceKey = array_search($this->faker->randomElement($references), $references);
        if ($randomReferenceKey !== false)
            return $this->getReference($randomReferenceKey, $class);
        return null;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param int $count
     * @param bool $unique
     * @param int $maxRetries
     * @return array<T>
     */
    protected function getRandomReferences(string $class, int $count, bool $unique = false, ?string $suffix = null, int $maxRetries = 1000): array
    {
        $references = [];
        while (count($references) < $count && (!$unique || ($maxRetries-- > 0))) {
            $reference = $this->getRandomReference($class, $suffix);
            if (!$unique || !in_array($reference, $references))
            {
                $references[] = $reference;
            }
        }

        return $references;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param int $count
     * @return array<T>
     */
    protected function getRandomUniqueReferences(string $class, int $count, ?string $suffix = null): array
    {
        if($count > $this->_getNumberOfReferenceByClass($class, $suffix)) {
            throw new LogicException('You\'re trying to get more unique references than were created.');
        }

        $references = [];
        while (count($references) < $count) {
            $randomRef = $this->getRandomReference($class, $suffix);
            if(!in_array($randomRef, $references)){
                $references[] = $randomRef;
            }
        }

        return $references;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param int $count
     * @param callable $comparisonFunc
     * @return array<T>
     */
    protected function getRandomUniqueReferencesBy(string $class, int $count, callable $comparisonFunc, ?string $suffix = null): array
    {
        if($count > $this->_getNumberOfReferenceByClass($class, $suffix)) {
            throw new LogicException('You\'re trying to get more unique references than were created.');
        }

        $references = [];
        while (count($references) < $count) {
            $randomRef = $this->getRandomReference($class, $suffix);
            if(!in_array($randomRef, $references) && $comparisonFunc($randomRef) === true){
                $references[] = $randomRef;
            }
        }

        return $references;
    }

    /**
     * Return the number of references of the corresponding $class
     *
     * @template T of object
     * @param class-string<T> $class the targeted fixture class
     * @return int              Number of reference satisfying the $class passed
     */
    private function _getNumberOfReferenceByClass(string $class, ?string $suffix = null): int
    {
        return count($this->getAllReferences($class, $suffix));
    }

    public function getTargetEntityManagerName() : string
    {
        return 'default';
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param int $count
     * @param string|null $suffix
     * @return string
     */
    private function _getReferenceName(string $class, int $count, ?string $suffix) : string
    {
        $referenceBaseName = implode('_', array_filter([u($class)->snake()->toString(), $suffix]));
        return sprintf('%s_%d', $referenceBaseName, $count);
    }
}
