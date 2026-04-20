<?php

namespace Workbench\App\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Enums\BadgeStyle;
use SchenkeIo\PackagingTools\Exceptions\PackagingToolException;
use SchenkeIo\PackagingTools\Markdown\ClassReader;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;
use SchenkeIo\PackagingTools\Setup\ProjectContext;

class MakeMarkdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:markdown';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     * @throws PackagingToolException
     * @throws \ReflectionException
     */
    public function handle(): void
    {
        $projectContext = new ProjectContext;
        $mda = new MarkdownAssembler('workbench/resources/md', $projectContext);

        $mda->autoHeader();

        $mda->addMarkdown('introduction.md');

        $mda->toc();

        $mda->addMarkdown('installation.md');
        $mda->addMarkdown('configuration.md');
        $mda->addMarkdown('errors.md');
        $mda->addMarkdown('example.md');

        // Key Classes
        $tableKeyClasses = [['Class', 'Summary']];
        foreach ([Service::class, UserData::class] as $classname) {
            $reader = ClassReader::fromClass($classname, $projectContext);
            $data = $reader->getClassDataFromClass($classname);
            $tableKeyClasses[] = ['`'.$data['short'].'`', $data['summary']];
        }
        $mda->addText('## Key Classes');
        $mda->tables()->fromArray($tableKeyClasses);

        $mda->addMarkdown('providers.md');

        // Providers overview
        $tableProviders = [['ID', 'Detail', 'Link']];
        foreach (Service::cases() as $case) {
            $provider = $case->provider();
            $reader = ClassReader::fromClass(get_class($provider), $projectContext);
            $data = $reader->getClassDataFromClass(get_class($provider));

            $summary = $data['summary'] ?? '??';
            $link = $data['link'][0] ?? '??';
            $tableProviders[] = [$case->name, $summary, $link];
        }
        $mda->tables()->fromArray($tableProviders);

        // Providers details
        foreach (Service::cases() as $case) {
            $provider = $case->provider();
            $reader = ClassReader::fromClass(get_class($provider), $projectContext);
            $data = $reader->getClassDataFromClass(get_class($provider));

            $summary = $data['summary'] ?? '??';
            $link = $data['link'][0] ?? '??';
            $description = $data['description'] ?? '??';

            $mda->addText('## '.ucfirst($case->name).' Provider');
            $mda->addText("First go to $link\n$description");

            $mda->addText('Edit the `.env` file in your Laravel project and add the credentials:');
            $env = "```dotenv\n";
            foreach ($provider->env() as $key => $value) {
                $env .= "$value=...\n";
            }
            $env .= '```';
            $mda->addText($env);

            $mda->addText('Edit the `config/services.php` file:');
            $php = "```php\n";
            $php .= "    '".$case->name."' => [\n";
            foreach ($provider->env() as $key => $value) {
                $php .= "        '$key' => env('$value'),\n";
            }
            $php .= "    ],\n```";
            $mda->addText($php);
            $mda->addText('You do not need to configure the callback URL, it will be automatically added');
        }

        $mda->writeMarkdown('README.md');

        $this->info('README.md generated successfully.');

        // Update SVG badges
        MakeBadge::makeCoverageBadge('build/coverage/clover.xml', $projectContext)
            ->store('.github/coverage.svg', BadgeStyle::Flat);
        MakeBadge::makePhpStanBadge('phpstan.neon', '2563eb', $projectContext)
            ->store('.github/phpstan.svg', BadgeStyle::Flat);
    }
}
