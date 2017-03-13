<?php

namespace Pumukit\ImportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\StatsBundle\Document\ViewsLog;

class ImportViewsCommand extends ContainerAwareCommand
{
    private $dm;
    private $repo;

    protected function configure()
    {
        $this
          ->setName('import:pumukit:views')
          ->setDescription('Import PuMuKIT1.7 views metadata from file to database')
          ->addOption('data', 'd', InputOption::VALUE_REQUIRED, 'Path of the CSV file to import')
          ->setHelp(<<<'EOT'
                    Command to import PuMuKIT1.7 views.

                    The --data parameter has to be used to add views from a csv file.

EOT
                    );
    }

    private function initParameters()
    {
        $this->dm = $this
                  ->getContainer()
                  ->get('doctrine_mongodb')
                  ->getManager();
        $this->repo = $this
                    ->dm
                    ->getRepository('PumukitSchemaBundle:MultimediaObject');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $cache = array();

        /*
          +------------+--------------+------+-----+---------+----------------+
          | Field      | Type         | Null | Key | Default | Extra          |
          +------------+--------------+------+-----+---------+----------------+
          | id         | int(11)      | NO   | PRI | NULL    | auto_increment |
          | file_id    | int(11)      | YES  | MUL | NULL    |                |
          | ip         | varchar(15)  | NO   |     | NULL    |                |
          | navigator  | varchar(255) | NO   |     | NULL    |                |
          | referer    | varchar(255) | NO   |     | NULL    |                |
          | created_at | datetime     | YES  |     | NULL    |                |
          +------------+--------------+------+-----+---------+----------------+
          6 rows in set (0.00 sec)
         */
        $this->initParameters();

        if (!($dataPath = $input->getOption('data'))) {
            $output->writeln('<error>ATTENTION:</error> This operation should use a path to import.');
            $output->writeln('');
            $output->writeln('Please run the operation with --data and the path of the file or directory to import the Series metadata from.');

            return;
        }

        if (($file = fopen($dataPath, 'r')) === false) {
            $output->writeln('<error>Error opening '.$dataPath.": fopen() returned 'false' </error>");

            return -1;
        }

        $i = 0;
        while (($currentRow = fgetcsv($file)) !== false) {
            ++$i;

            if (6 != count($currentRow)) {
                //TODO log eror
                continue;
            }

            $tag = 'pumukit1id:'.$currentRow[1];

            if (array_key_exists($tag, $cache)) {
                $multimediaObject = $cache[$tag];
            } else {
                $multimediaObject = $this->repo->createQueryBuilder()
                                  ->field('tracks.tags')->equals($tag)
                                  ->getQuery()->getSingleResult();

                if (!$multimediaObject) {
                    //TODO log eror
                    continue;
                }

                $cache[$tag] = $multimediaObject;
            }



            $track = $multimediaObject->getTrackWithTag($tag);

            if (!$track) {
                //TODO log eror
                continue;
            }

            $log = new ViewsLog('http://ehutb.ehu.es/es/video/index/uuid/XXXXXXXXXXXXX.html',
                                $currentRow[2],
                                $currentRow[3],
                                $currentRow[4],
                                $multimediaObject->getId(),
                                $multimediaObject->getSeries()->getId(),
                                $track->getId(),
                                null);

            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $currentRow[5]);
            $log->setDate($date);

            $this->dm->persist($log);

            if (0 == $i % 50) {
                $this->dm->flush();
                $this->dm->clear();
                $output->write('.');
            }
        }

        $this->dm->flush();
    }
}
