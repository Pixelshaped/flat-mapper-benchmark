<?php
declare(strict_types=1);


namespace App\Command;


use App\DTO\AuthorDTO;
use App\DTO\BookDTO;
use App\DTO\BookScalarDTO;
use App\Entity\Author;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pixelshaped\FlatMapperBundle\FlatMapper;
use ReflectionFunction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Environment;

#[AsCommand(
    name: 'app:benchmark',
    description: 'Execute benchmark and generate README.md as a result',
)]
final class BenchmarkCommand extends Command
{
    private array $benchmarks = [];
    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly FlatMapper $flatMapper,
        private readonly Environment $twig,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }
    private function measure(callable $callback, int $times = 1)  {

        $duration = 0;
        $memory = 0;
        for ($i = 0; $i < $times; $i++) {
            $this->entityManager->clear();
            memory_reset_peak_usage();
            $stopwatch = new Stopwatch();
            $stopwatch->start('cmdExec');
            $callback();
            $duration += ($stopwatch->stop('cmdExec'))->getDuration();
            $memory += (memory_get_peak_usage()/1024/1024);
        }

        $duration = $duration / $times;
        $memory = $memory / $times;

        return ['duration' => $duration, 'memory' => $memory];
    }

    function getSource(callable $method){

        $func = new ReflectionFunction($method);

        $f = $func->getFileName();
        $start_line = $func->getStartLine();
        $end_line = $func->getEndLine() - 1;

        $source = file($f);
        $source = implode('', array_slice($source, 0, count($source)));
        $source = preg_split("/".PHP_EOL."/", $source);

        $body = '';
        for($i=$start_line; $i<$end_line; $i++)
            $body.="{$source[$i]}\n";

        return $body;
    }

    private function addBenchmark(string $category, string $title, callable $benchmarkCallable): void
    {
        $this->io->text($category.' - '.$title);
        $this->benchmarks[$category][$title]['results'] = $this->measure($benchmarkCallable);
        $this->benchmarks[$category][$title]['source'] = str_replace('            ', '', $this->getSource($benchmarkCallable));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Benchmark');

        $this->addBenchmark('Nested', 'FlatMapper DTOs', function ()  {
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
        });

        $this->addBenchmark('Nested', 'Doctrine entity', function () {
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
        });

        $this->addBenchmark('Scalar','FlatMapper mapping DQL', function () {
            $qb = $this->bookRepository->createQueryBuilder('book');
            $result = $qb->select('book.id, book.title, book.isbn')
                ->getQuery()
                ->toIterable();
            $result = $this->flatMapper->map(BookScalarDTO::class, $result);
        });

        $this->addBenchmark('Scalar','Doctrine DTO', function () {
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
        });

        $this->addBenchmark('Scalar','Manual mapping DQL', function () {
            $qb = $this->bookRepository->createQueryBuilder('book');
            $result = $qb->select('book.id, book.title, book.isbn')
                ->getQuery()
                ->toIterable();

            $resultSet = [];
            foreach ($result as $productEdit) {
                $resultSet[] = new BookScalarDTO(...$productEdit);
            }
        });

        $this->addBenchmark('Scalar', 'Flatmapper mapping SQL', function () {
            $query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');
            $result = $this->flatMapper->map(BookScalarDTO::class, $query->iterateAssociative());
        });

        $this->addBenchmark('Scalar','Manual mapping SQL', function () {
            $query = $this->entityManager->getConnection()->executeQuery('SELECT id, title, isbn FROM book');

            $resultSet = [];
            foreach($query->iterateAssociative() as $row) {
                $resultSet[] = new BookScalarDTO(...$row);
            }
        });

        $readmeFileContent = $this->twig->render('README.md.twig', [
            'benchmarks' => $this->benchmarks,
        ]);

        file_put_contents($this->projectDir.'/README.md', $readmeFileContent);

        return Command::SUCCESS;
    }
}
