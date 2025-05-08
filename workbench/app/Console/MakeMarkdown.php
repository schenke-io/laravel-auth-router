<?php

namespace Workbench\App\Console;

use Illuminate\Console\Command;
use SchenkeIo\LaravelAuthRouter\Auth\Service;
use SchenkeIo\PackagingTools\Badges\BadgeStyle;
use SchenkeIo\PackagingTools\Badges\MakeBadge;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

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
     */
    public function handle()
    {
        $mda = new MarkdownAssembler('workbench/resources/md');
        $mda->storeTestBadge('run-test.yml', BadgeStyle::Flat);
        $mda->storeVersionBadge(BadgeStyle::Flat);
        $mda->storeDownloadBadge(BadgeStyle::Flat);
        $mda->storeLocalBadge('coverage', '.github/coverage.svg');
        $mda->storeLocalBadge('phpstan', '.github/phpstan.svg');

        // header
        $mda->addText("# Laravel Auth Router\n\n Social Login made easy\n");
        $mda->addBadges();
        $mda->addMarkdown('introduction.md');
        $mda->addTableOfContents();

        $mda->addMarkdown('installation.md');

        $mda->addMarkdown('providers.md');

        // provider overview
        $table[] = ['ID', 'Detail', 'Link'];
        foreach (Service::cases() as $case) {
            $providerClass = get_class($case->provider());
            $data = $mda->getClassData($providerClass);
            $title = $data['summary'] ?? '??';
            $link = $data['link'][0] ?? '??';
            $table[] = [$case->name, $title, $link];
        }
        $mda->addTableFromArray($table);

        // provider details
        foreach (Service::cases() as $case) {
            $providerClass = get_class($case->provider());
            $data = $mda->getClassData($providerClass);
            $title = $data['summary'] ?? '??';
            $link = $data['link'][0] ?? '??';
            $description = $data['description'] ?? '??';

            $mda->addText("\n## ".ucfirst($case->name)." Provider\n\n");
            $mda->addText("First go to $link\n$description\n\n");
            $mda->addText("Edit the `.env` file in your Laravel project and add the credentials:\n");
            $env = "```dotenv\n";
            foreach ($case->provider()->env() as $key => $value) {
                $env .= "$value=...\n";
            }
            $env .= "``` \n";
            $mda->addText($env);
            $mda->addText("Edit the config/services.php file:\n\n");
            $php = "```php\n";
            $php .= "    '".$case->name."' => [\n";
            foreach ($case->provider()->env() as $key => $value) {
                $php .= "        '$key' => env('$value'),\n";
            }
            $php .= "    ],\n``` \nYou do not need to configure the callback URL, it will be automatically added\n\n";
            $mda->addText($php);
        }

        $mda->writeMarkdown('README.md');

        $this->info('Markdown files written successfully.');

        MakeBadge::makeCoverageBadge('build/coverage/clover.xml', '32CD32')
            ->store('.github/coverage.svg', BadgeStyle::Flat);
        MakeBadge::makePhpStanBadge('phpstan.neon')
            ->store('.github/phpstan.svg', BadgeStyle::Flat);
    }
}
