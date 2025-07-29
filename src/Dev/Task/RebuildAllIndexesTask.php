<?php

namespace BimTheBam\Meilisearch\Dev\Task;

use BimTheBam\Meilisearch\Index;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Class RebuildAllIndexesTask
 * @package BimTheBam\Meilisearch\Dev\Task
 */
class RebuildAllIndexesTask extends BuildTask
{
    /**
     * @var string
     */
    protected static string $commandName = 'meilisearch-rebuild-all-indexes';
    /**
     * @var string
     */
    protected string $title = 'Rebuild all meilisearch indexes';

    /**
     * @var string
     */
    protected static string $description = 'Rebuild all meilisearch indexes';


    /**
     * @param $request
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $stage = $input->getOption('stage');

        Versioned::withVersionedMode(function () use ($stage) {
            if ($stage) {
                Versioned::set_stage($stage);
            }

            foreach (ClassInfo::subclassesFor(Index::class, false) as $indexClass) {
                /** @var Index $index */
                $index = Injector::inst()->create($indexClass);

                $index->rebuild();
            }
        });

        return Command::SUCCESS; // Indicate success
    }

    public function getOptions(): array
    {
        return [
            new InputOption(
                'stage',
                null,
                InputOption::VALUE_OPTIONAL,
                'Stage to rebuild indexes for. Defaults to "Live".',
            ),
        ];
    }
}
