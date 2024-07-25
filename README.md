# pixelshaped/flat-mapper-bundle benchmark

This repository hosts some performance benchmarks for [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) and Doctrine.

It's also meant to prove the relevance of using DTOs when trying to squeeze the best performance out of your backend, all architectural considerations aside.

While Doctrine entities are a fantastic tool to prototype, or even use on some of your low traffic pages, on hotpaths using Doctrine entities can result in poor performance. It's not the fault of Doctrine itself - it's pretty fast. But:

- Entities inflate your Model: you're querying for a lot of things that you're not going to use
- Entities leave you at risk of N+1 queries. You can tame those by adding `join` statements, but at some point you're eventually going to access a getter for which the `join` doesn't exist yet, and forget to add it.

In the end your Model should rarely ever be larger than your View and the proper way to achieve this is to use DTOs. Doctrine provides a way to retrieve scalar DTOs, but you're on your own if you need nested DTOs. The creation of [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) arose from that situation and aims to fill this gap.

## Summary
| Category       | Method                      | Duration    | Memory   |
|----------------|-----------------------------|-------------|----------|
| NestedBench    | benchFlatMapperDTOs         | 278.072ms   | 27.263mb |
| NestedBench    | benchDoctrineEntities       | 761.965ms   | 46.137mb |
| NestedBench    | benchDoctrineEntitiesWithN1 | 10093.759ms | 39.846mb |
| DQLScalarBench | benchFlatMapperWithDQL      | 68.705ms    | 12.583mb |
| DQLScalarBench | benchDoctrineDTOs           | 59.368ms    | 10.486mb |
| DQLScalarBench | benchManualMappingWithDQL   | 62.124ms    | 12.583mb |
| SQLScalarBench | benchFlatMapperWithSQL      | 38.187ms    | 8.389mb  |
| SQLScalarBench | benchManualMappingWithSQL   | 29.843ms    | 8.389mb  |



## NestedBench

This is the main and most relevant comparison: FlatMapper truly shines when using nested DTOs versus entities.

Using other DTO mappers based on entities will eventually result in lower performance than `benchDoctrineEntities`.


### benchFlatMapperDTOs

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
| 278.072ms | 27.263mb |


### benchDoctrineEntities

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

| Duration  | Memory   |
|-----------|----------|
| 761.965ms | 46.137mb |


### benchDoctrineEntitiesWithN1

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
| 10093.759ms | 39.846mb |


## DQLScalarBench

In these tests we can see that all methods to map DQL results to a DTO only supporting scalar properties are almost as effective.

Doctrine DTO has a little edge as it creates the DTOs during the results hydration, where other methods have to do it in another loop.

FlatMapper is a little slower as it uses named parameters internally instead of mapping data in the same order as it has been queried.


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
| 68.705ms | 12.583mb |


### benchDoctrineDTOs

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select(sprintf('NEW %s(book.id, book.title, book.isbn)', BookScalarDTO::class))
    ->getQuery()
    ->getResult();
```

| Duration | Memory   |
|----------|----------|
| 59.368ms | 10.486mb |


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
| 62.124ms | 12.583mb |


## SQLScalarBench

This is a bonus test to show how close in terms of performance FlatMapper is from manually mapping data to scalar DTOs with raw SQL results


### benchFlatMapperWithSQL

```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
$result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
```

| Duration | Memory  |
|----------|---------|
| 38.187ms | 8.389mb |


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
| 29.843ms | 8.389mb |


## Execute the benchmark yourself
```bash
# Install dependencies
composer install

# Launch database container
docker-compose up -d

# Create database
bin/console doctrine:database:create

# Execute migrations
bin/console doctrine:migrations:migrate

# Load fixtures
bin/console doctrine:fixtures:load

# Execute benchmark
bin/console app:benchmark 
```
## Contribute

Do not hesitate to open a pull request to add more benchmarks 
