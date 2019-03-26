<?php

namespace Pumukit\ImportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Pumukit\SchemaBundle\Document\Series;

class ImportCommand extends ContainerAwareCommand
{
    private $importSeriesService;

    protected function configure()
    {
        $this
          ->setName('import:pumukit:series')
          ->setDescription('Import PuMuKIT1.7 Series metadata from file to database')
          ->addOption('data', 'd', InputOption::VALUE_REQUIRED, 'Path of the XML file or directory to import')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Set this parameter to execute this action')
          ->setHelp(<<<'EOT'
                    Command to import PuMuKIT1.7 Series metadata.

                    The --data parameter has to be used to add metadata from directory or xml file.

                    The --force parameter has to be used to actually modifiy the database.

EOT
                    );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initParameters();

        if (!$input->getOption('force')) {
            $output->writeln('<error>ATTENTION:</error> This operation should not be executed in a production environment.');
            $output->writeln('');
            $output->writeln('<info>Would modify the database</info>');
            $output->writeln('Please run the operation with --force to execute and with --data to import the Series metadata.');
            $output->writeln('<error>All data will be lost!</error>');
        } elseif (!($dataPath = $input->getOption('data'))) {
            $output->writeln('<error>ATTENTION:</error> This operation should use a path to import.');
            $output->writeln('');
            $output->writeln('Please run the operation with --data and the path of the file or directory to import the Series metadata from.');
        } else {
            $extension = pathinfo($dataPath, PATHINFO_EXTENSION);
            if ('xml' === $extension) {
                $errors = $this->importXMLFile($dataPath, $output);

                if (0 === count($errors)) {
                    $output->writeln('<info>All series successfully imported.</info>');
                    $output->writeln('');
                } else {
                    $output->writeln('<error>ATTENTION: There were '.count($errors).' errors during import:</error>');
                    foreach ($errors as $index => $message) {
                        $output->writeln('<error>ERROR '.$index.'</error>');
                        $output->writeln($message);
                        $output->writeln('');
                    }
                    $output->writeln('');
                }
            } elseif ('' === $extension) {
                $finder = new Finder();
                $finder->files()->in($dataPath);
                $finder->sortByName();
                $files = array();
                $errors = array();
                foreach ($finder as $f) {
                    $filePath = $f->getRealpath();
                    if ('xml' === pathinfo($filePath, PATHINFO_EXTENSION)) {
                        $files[] = $filePath;
                        $fileErrors = $this->importXMLFile($filePath, $output);
                        $errors = array_merge($errors, $fileErrors);
                    }
                }
                if (0 === count($files)) {
                    $output->writeln('<error>ATTENTION:</error> This operation should use a valid path to import.');
                    $output->writeln('<info>This directory has no XML files</info>');
                    $output->writeln('');
                    $output->writeln('Please run the operation with --data and the path of a XML file or directory to import the Series metadata from.');
                }
                if (0 === count($errors)) {
                    $output->writeln('<info>All series successfully imported.</info>');
                    $output->writeln('');
                } else {
                    $output->writeln('<error>ATTENTION: There were '.count($errors).' errors during import:</error>');
                    foreach ($errors as $index => $message) {
                        $output->writeln('<error>ERROR '.$index.'</error>');
                        $output->writeln($message);
                        $output->writeln('');
                    }
                    $output->writeln('');
                }
            } else {
                $output->writeln('<error>ATTENTION:</error> This operation should use a valid path to import.');
                $output->writeln('');
                $output->writeln('Please run the operation with --data and the path of a XML file or directory to import the Series metadata from.');
            }
        }
    }

    private function initParameters()
    {
        $this->importSeriesService = $this->getContainer()->get('pumukit_import.series');
    }

    private function importXMLFile($filePath, OutputInterface $output)
    {
        $errors = array();
        try {
            $output->writeln("Trying to import Series file '".$filePath."' into Pumukit ...");

            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new \Exception($errstr, $errno);
            });
            $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
            $xmlArray = $this->arrayClean($xml);

            $series = $this->importSeriesService->setImportedSeries($xmlArray);
            if ($series instanceof Series) {
                $output->writeln("Imported Series from PuMuKIT1.7 id '".$series->getProperty('pumukit1id')."' into MongoDB id '".$series->getId())."'";
            }
        } catch (\Exception $e) {
            restore_error_handler();
            $message = $e->getMessage();
            if (null == $message) {
                $message = $e->xdebug_message;
            }
            $outputMessage = 'File: '.$filePath.' Thrown error: '.$message.'. Trace: '.(string) $e;
            $output->writeln($outputMessage);
            $errors[] = $outputMessage;
        }

        return $errors;
    }

    /**
     * Convert XML into an array and cleans wrong keys.
     *
     * @param $xml
     *
     * @return mixed
     */
    private function arrayClean($xml)
    {
        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        $this->recursive_unset($xmlArray, 'comment');

        return $xmlArray;
    }

    /**
     * @param $array
     * @param $unwanted_key
     */
    public function recursive_unset(&$array, $unwanted_key)
    {
        unset($array[$unwanted_key]);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursive_unset($value, $unwanted_key);
            }
        }
    }
}
