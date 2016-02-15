<?php

namespace Bolt\Docs\Command;

use Cocur\Slugify\Slugify;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Dumper;

/**
 * Class BuildDocumentation console command
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BuildDocumentation extends Command
{

    public $rootdir = '/version/';
    public $versions = 'versions.yml';

    protected function configure()
    {
        $this->setName("bolt:build-docs")
            ->addArgument('branches', InputArgument::IS_ARRAY, ['master'])
            ->setDescription("Builds documentation from an array of git branches");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $versionsArray = [];

        $slugify = new Slugify();

        foreach ($input->getArgument('branches') as $branch) {
            $this->getSubTree($branch, $output, './source_docs/');
            $versionsArray[$branch] = $slugify->slugify($branch);
        }

        $dumper = new Dumper();

        // Write the passed in versions to the yml file
        $path = getcwd() . '/app/' . $this->versions;
        file_put_contents($path, $dumper->dump($versionsArray));
        $output->writeln("<info>Versions saved to $path</info>");
    }

    protected function getSubTree($branch, $output, $prefix = '')
    {
        $directory = getcwd() . $this->rootdir;

        $slugify = new Slugify();

        $tree = shell_exec('git ls-tree -r ' . $branch . " " . $prefix);
        $tree = array_filter(explode("\n", $tree));
        $filelist = array_map('str_getcsv', $tree, array_fill(0, count($tree), "\t"));

        foreach ($filelist as $source) {
            $file = $source[1];

            $content = shell_exec('git show ' . $branch . ":" . $file);

            $folderName = $slugify->slugify($branch);

            @mkdir(dirname($directory . $folderName . '/' . $file), 0755, true);
            file_put_contents($directory . $folderName . '/' . $file, $content);
        }

        $menu = shell_exec('git show ' . $branch . ":menu_docs.yml");
        file_put_contents($directory . $folderName . '/menu_docs.yml', $menu);

        $output->writeln("<info>Branch $branch written to $directory$folderName</info>");
    }

}