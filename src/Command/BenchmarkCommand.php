<?php
declare(strict_types=1);


namespace App\Command;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Twig\Environment;

#[AsCommand(
    name: 'app:benchmark',
    description: 'Execute benchmark and generate README.md as a result',
)]
final class BenchmarkCommand extends Command
{
    private array $benchmarkResults = [];

    private const array BENCH_ORDER = ['NestedBench', 'DQLScalarBench', 'SQLScalarBench'];

    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Benchmark');

        $process = (new Process(['./vendor/bin/phpbench', 'run', '-d', 'report.xml', '--report=default']))->setTimeout(null);
        $process->run(fn ($type, $buffer) => $output->write($buffer));

        $xml = simplexml_load_file($this->projectDir.'/report.xml');
        foreach ($xml->suite->xpath('//benchmark') as $benchmark) {
            $className = (string)$benchmark['class'];
            $reflectionClass = new \ReflectionClass($className);
            $shortName = $reflectionClass->getShortName();
            foreach ($benchmark->children() as $subject) {
                $methodName = (string)$subject['name'];
                $source = $this->getSource($className, $methodName);
                $duration = round($subject->variant->iteration['time-avg']/1000, 3).'ms';
                $memory = round($subject->variant->iteration['mem-real']/1000000, 3).'mb';
                $this->benchmarkResults[$shortName]['description'] = $this->getDocComment($reflectionClass);
                $this->benchmarkResults[$shortName]['results'][$methodName] = [
                    'source' => $source,
                    'duration' => $duration,
                    'memory' => $memory,
                ];
            }
        }

        $this->benchmarkResults =array_merge(array_flip(self::BENCH_ORDER), $this->benchmarkResults);

        $readmeFileContent = $this->twig->render('README.md.twig', [
            'benchmarks' => $this->benchmarkResults,
        ]);
        file_put_contents($this->projectDir.'/README.md', $readmeFileContent);

        $io->success('Benchmark generated');

        return Command::SUCCESS;
    }

    private function getDocComment(\ReflectionClass $class): string
    {
        $comment = $class->getDocComment();
        if($comment === false) {
            return '';
        }
        $comment = explode(PHP_EOL, $comment);
        $comment = array_slice($comment, 1, -1);
        $comment = array_map(fn($row) => trim($row, ' *'), $comment);
        return implode(PHP_EOL.PHP_EOL, $comment);

    }

    private function getSource(string $class, string $method){

        $func = new \ReflectionMethod($class, $method);

        $f = $func->getFileName();
        $startLine = $func->getStartLine() + 1;
        $endLine = $func->getEndLine() - 1;

        $source = file($f);
        $source = implode('', array_slice($source, 0, count($source)));
        $source = preg_split("/".PHP_EOL."/", $source);

        $body = '';
        for($i = $startLine; $i < $endLine; $i++) {
            $body .= "{$source[$i]}".PHP_EOL;
        }

        return str_replace('        ', '', $body);
    }
}
