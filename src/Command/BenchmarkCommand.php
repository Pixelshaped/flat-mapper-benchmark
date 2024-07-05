<?php
declare(strict_types=1);


namespace App\Command;


use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand(
    name: 'app:benchmark',
    description: 'Execute benchmark and generate README.md as a result',
)]
final class BenchmarkCommand extends Command
{
    private SymfonyStyle $io;

    private array $benchmarkResults = [];

    public function __construct(
        private readonly string $projectDir,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Benchmark');

//        $process = (new Process(['./vendor/bin/phpbench', 'run', '-d', 'report.xml', '--report=default']))->setTimeout(null);
//        $process->run(fn ($type, $buffer) => $output->write($buffer));

        $xml = simplexml_load_file($this->projectDir.'/report.xml');
        foreach ($xml->suite->xpath('//benchmark') as $benchmark) {
            $className = (string)$benchmark['class'];
            foreach ($benchmark->children() as $subject) {
                $methodName = (string)$subject['name'];
                $source = $this->getSource($className, $methodName);
                $duration = round($subject->variant->iteration['time-avg']/1000, 3).'ms';
                $memory = round($subject->variant->iteration['mem-real']/1000000, 3).'mb';
                $this->benchmarkResults[$className][$methodName] = [
                    'source' => $source,
                    'duration' => $duration,
                    'memory' => $memory,
                ];
            }
        }

        $readmeFileContent = $this->twig->render('README.md.twig', [
            'benchmarks' => $this->benchmarkResults,
        ]);
        file_put_contents($this->projectDir.'/README.md', $readmeFileContent);

        return Command::SUCCESS;
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
