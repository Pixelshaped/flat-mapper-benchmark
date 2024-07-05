# pixelshaped/flat-mapper-bundle benchmark

This repository hosts some performance benchmarks for [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) and Doctrine.

It's also meant to prove the relevance of using DTOs when trying to squeeze the most performance out of your backend, all architectural considerations aside.

While Doctrine entities are a fantastic tool to prototype, or even use on some of your pages, on hotpaths using Doctrine entities can result in poor performance. It's not the fault of Doctrine itself - it's pretty fast. But:

- Entities inflate your Model: you're querying for a lot of things that you're not going to use
- Entities leave you at risk of N+1 queries. You can tame those by adding `join` statements, but at some point you're eventually going to access a getter for which the `join` doesn't exist yet, and forget to add it.

In the end your Model should rarely ever be larger than your View and the proper way to achieve this is to use DTOs. Doctrine provides a way to retrieve scalar DTOs, but you're on your own if you need nested DTOs. The creation of [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) arose from that situation and aims to fill this gap.


## \App\Tests\Benchmarks\SQLBench


### benchFlatMapperWithSQL

```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
$result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
```

| Duration | Memory  |
|----------|---------|
| 23.138ms | 8.389mb |


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
| 19.723ms | 8.389mb |


## \App\Tests\Benchmarks\ScalarBench


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
| 55.895ms | 10.486mb |


### benchDoctrineDTOs

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select(sprintf('NEW %s(book.id, book.title, book.isbn)', BookScalarDTO::class))
    ->getQuery()
    ->getResult();
```

| Duration | Memory   |
|----------|----------|
| 51.416ms | 10.486mb |


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
| 52.689ms | 10.486mb |


## \App\Tests\Benchmarks\NestedBench


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
| 244.825ms | 25.166mb |


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

| Duration  | Memory  |
|-----------|---------|
| 686.664ms | 44.04mb |


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

| Duration   | Memory   |
|------------|----------|
| 2314.667ms | 37.749mb |


## Execute the benchmark yourself
```bash
# Launch database container
docker-compose up -d

# Execute migrations
bin/console doctrine:migrations:migrate

# Load fixtures
bin/console doctrine:fixtures:load

# Execute benchmark
bin/console app:benchmark 
```
## Contribute

Do not hesitate to open a pull request to add more benchmarks 
