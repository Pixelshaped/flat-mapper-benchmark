# pixelshaped/flat-mapper-bundle benchmark

This repository hosts some performance benchmarks for [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) and Doctrine.

It's also meant to prove the relevance of using DTOs when trying to squeeze the best performance out of your backend, all architectural considerations aside.

While Doctrine entities are a fantastic tool to prototype, or even use on some of your low traffic pages, on hotpaths using Doctrine entities can result in poor performance. It's not the fault of Doctrine itself - it's pretty fast. But:

- Entities inflate your Model: you're querying for a lot of things that you're not going to use
- Entities leave you at risk of N+1 queries. You can tame those by adding `join` statements, but at some point you're eventually going to access a getter for which the `join` doesn't exist yet, and forget to add it.

In the end your Model should rarely ever be larger than your View and the proper way to achieve this is to use DTOs. Doctrine provides a way to retrieve scalar DTOs, but you're on your own if you need nested DTOs. The creation of [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) arose from that situation and aims to fill this gap.

## Summary
| Category       | Method                                             | Duration    | Memory   |
|----------------|----------------------------------------------------|-------------|----------|
| NestedBench    | benchFlatMapperDTOs                                | 267.946ms   | 25.166mb |
| NestedBench    | benchDoctrineEntities                              | 735.431ms   | 44.04mb  |
| NestedBench    | benchDoctrineEntitiesWithN1                        | 10974.221ms | 39.846mb |
| NestedBench    | benchDoctrineEntitiesWithSyliusAssociationHydrator | 799.386ms   | 44.04mb  |
| DQLScalarBench | benchFlatMapperWithDQL                             | 66.184ms    | 12.583mb |
| DQLScalarBench | benchDoctrineDTOs                                  | 56.644ms    | 12.583mb |
| DQLScalarBench | benchManualMappingWithDQL                          | 61.203ms    | 12.583mb |
| SQLScalarBench | benchFlatMapperWithSQL                             | 38.105ms    | 8.389mb  |
| SQLScalarBench | benchManualMappingWithSQL                          | 33.514ms    | 8.389mb  |



## NestedBench

This is the main and most relevant comparison: FlatMapper truly shines when using nested DTOs versus nested entities.


### benchFlatMapperDTOs

In this benchmark we&#039;re using FlatMapper to map a DTO that has the same number of fields than the entity it mimics (in benchDoctrineEntities). It&#039;s the fairest comparison. In real life situations entities tend to have a lot of unneeded properties.

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select('book.id, book.title, book.isbn, authors.id as author_id, authors.firstName as author_first_name, authors.lastName as author_last_name, reviews.id as review_id, reviews.rating as review_rating')
    ->leftJoin('book.authors', 'authors')
    ->leftJoin('book.reviews', 'reviews')
    ->getQuery()
    ->getResult();
$result = $this->flatMapper->map(BookDTO::class, $result);
foreach ($result as $book) {
    $this->bookDisplayer->display($book);
}
```

| Duration  | Memory   |
|-----------|----------|
| 267.946ms | 25.166mb |


### benchDoctrineEntities

This is the base Doctrine benchmark. Using other DTO mappers based on entities will display similar performance.

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb
    ->leftJoin('book.authors', 'authors')
    ->addSelect('authors')
    ->leftJoin('book.reviews', 'reviews')
    ->addSelect('reviews')
    ->getQuery()
    ->getResult();
foreach ($result as $book) {
    $this->bookDisplayer->display($book);
}
```

| Duration  | Memory  |
|-----------|---------|
| 735.431ms | 44.04mb |


### benchDoctrineEntitiesWithN1

This benchmarks Doctrine when the user forgets to add JOIN statements and Doctrine has to make N+1 queries. It&#039;s not a fair comparison but illustrates how Doctrine behaves in such a situation (on one hand: it will always land on its feet, on the other hand: at what cost?)

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb
    ->getQuery()
    ->getResult();
foreach ($result as $book) {
    $this->bookDisplayer->display($book);
}
```

| Duration    | Memory   |
|-------------|----------|
| 10974.221ms | 39.846mb |


### benchDoctrineEntitiesWithSyliusAssociationHydrator

Out of curiosity I added a benchmark for Sylius Association-Hydrator. It&#039;s a widely used bundle that relies on PARTIAL queries (which restrict its usage to ORM2).

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb
    ->getQuery()
    ->getResult();

$sylusAssociationHydrator = new AssociationHydrator(
    $this->entityManager,
    $this->entityManager->getClassMetadata(Book::class)
);

$sylusAssociationHydrator->hydrateAssociations($result, [
    'authors',
    'reviews',
]);

foreach ($result as $book) {
    $this->bookDisplayer->display($book);
}
```

| Duration  | Memory  |
|-----------|---------|
| 799.386ms | 44.04mb |


## DQLScalarBench

In these tests we can see that all methods to map DQL results to a DTO only supporting scalar properties are almost as effective.

Doctrine DTO has a little edge as it creates the DTOs during the results hydration, where other methods have to do it in another loop.

FlatMapper is a little slower as it uses named parameters internally instead of mapping data to the constructor in the same order as it has been queried, but it&#039;s not significant.


### benchFlatMapperWithDQL


```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select('book.id, book.title, book.isbn')
    ->getQuery()
    ->toIterable();
$result = $this->flatMapper->map(BookScalarDTO::class, $result);
```

| Duration | Memory   |
|----------|----------|
| 66.184ms | 12.583mb |


### benchDoctrineDTOs


```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select(sprintf('NEW %s(book.id, book.title, book.isbn)', BookScalarDTO::class))
    ->getQuery()
    ->getResult();
```

| Duration | Memory   |
|----------|----------|
| 56.644ms | 12.583mb |


### benchManualMappingWithDQL


```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select('book.id, book.title, book.isbn')
    ->getQuery()
    ->toIterable();

$resultSet = [];
foreach ($result as $productEdit) {
    $resultSet[] = new BookScalarDTO(...$productEdit);
}
```

| Duration | Memory   |
|----------|----------|
| 61.203ms | 12.583mb |


## SQLScalarBench

This is a bonus test to show how close in terms of performance FlatMapper is from manually mapping data to scalar DTOs with raw SQL results


### benchFlatMapperWithSQL


```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
$result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
```

| Duration | Memory  |
|----------|---------|
| 38.105ms | 8.389mb |


### benchManualMappingWithSQL


```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
$resultSet = [];
foreach($query->iterateAssociative() as $row) {
    $resultSet[] = new BookScalarDTO(...$row);
}
```

| Duration | Memory  |
|----------|---------|
| 33.514ms | 8.389mb |


## Execute the benchmark yourself
```bash
# Install dependencies
composer install

# Launch database container
docker-compose up -d

# Create database, migrate, load fixtures
composer prepare-benchmark

# Execute benchmark
bin/console app:benchmark 
```
## Contribute

Do not hesitate to open a pull request to add more benchmarks 
