# pixelshaped/flat-mapper-bundle benchmark

This repository hosts some performance benchmarks for [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) and Doctrine.

It's also meant to prove the relevance of using DTOs when trying to squeeze the most performance out of your backend, all architectural considerations aside.

While Doctrine entities are a fantastic tool to prototype, or even use on some of your pages, on hotpaths using Doctrine entities can result in poor performance. It's not the fault of Doctrine itself - it's pretty fast. But:

- Entities inflate your Model: you're querying for a lot of things that you're not going to use
- Entities leave you at risk of N+1 queries. You can tame those by adding `join` statements, but at some point you're eventually going to access a getter for which the `join` doesn't exist yet, and forget to add it.

In the end your Model should rarely ever be larger than your View and the proper way to achieve this is to use DTOs. Doctrine provides a way to retrieve scalar DTOs, but you're on your own if you need nested DTOs. The creation of [pixelshaped/flat-mapper-bundle](https://github.com/Pixelshaped/flat-mapper-bundle) arose from that situation and aims to fill this gap.


## Nested


### FlatMapper DTOs

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select('book.id, book.title, book.isbn, authors.id as author_id, authors.firstName as author_first_name, authors.lastName as author_last_name')
    ->leftJoin('book.authors', 'authors')
    ->getQuery()
    ->getResult();
$result = $this->flatMapper->map(BookDTO::class, $result);
foreach($result as $book) {
    $author = reset($book->authors);
    if($author instanceof AuthorDTO) {
        $author->firstName;
    }
}
```

| Duration | Memory |
|----------|--------|
| 183ms    | 24MiB  |


### Doctrine entity

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb
    ->getQuery()
    ->getResult();
foreach($result as $book) {
    $author = $book->getAuthors()[0];
    if($author instanceof Author) {
        $author->getFirstName();
    }
}
```

| Duration | Memory |
|----------|--------|
| 9712ms   | 135MiB |


## Scalar


### FlatMapper mapping DQL

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select('book.id, book.title, book.isbn')
    ->getQuery()
    ->toIterable();
$result = $this->flatMapper->map(BookScalarDTO::class, $result);
```

| Duration | Memory |
|----------|--------|
| 104ms    | 127MiB |


### Doctrine DTO

```php
$qb = $this->bookRepository->createQueryBuilder('book');
$result = $qb->select(
    sprintf('
    NEW %s(
        book.id,
        book.title,
        book.isbn)',
        BookScalarDTO::class))
    ->getQuery()
    ->getResult();
```

| Duration | Memory |
|----------|--------|
| 57ms     | 127MiB |


### Manual mapping DQL

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

| Duration | Memory |
|----------|--------|
| 63ms     | 127MiB |


### Flatmapper mapping SQL

```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
$result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
```

| Duration | Memory |
|----------|--------|
| 38ms     | 127MiB |


### Manual mapping SQL

```php
$query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');

$resultSet = [];
foreach($query->iterateAssociative() as $row) {
    $resultSet[] = new BookScalarDTO(...$row);
}
```

| Duration | Memory |
|----------|--------|
| 24ms     | 127MiB |


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
