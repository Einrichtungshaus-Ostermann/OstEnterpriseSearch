<?php declare(strict_types=1);

/**
 * Einrichtungshaus Ostermann GmbH & Co. KG - Enterprise Search
 *
 * @package   OstEnterpriseSearch
 *
 * @author    Eike Brandt-Warneke <e.brandt-warneke@ostermann.de>
 * @copyright 2019 Einrichtungshaus Ostermann GmbH & Co. KG
 * @license   proprietary
 */

namespace OstEnterpriseSearch\Commands;

use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Enlight_Components_Db_Adapter_Pdo_Mysql as Db;
use Shopware\Components\Model\ModelManager;

class ImportSynonymsCommand extends ShopwareCommand
{
    /**
     * ...
     *
     * @var Db
     */
    private $db;

    /**
     * ...
     *
     * @var ModelManager
     */
    private $modelManager;

    /**
     * ...
     *
     * @var array
     */
    private $configuration;

    /**
     * @param Db $db
     * @param ModelManager $modelManager
     * @param array $configuration
     */
    public function __construct(Db $db, ModelManager $modelManager, array $configuration)
    {
        parent::__construct();
        $this->db = $db;
        $this->modelManager = $modelManager;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
        $output->writeln('reading synonyms');

        $handle = fopen ($this->configuration['synonymFile'],'r');


        $arr = array();

        // Datei zeilenweise auslesen, fgetcsv() anwenden, im Array $csv_array speichern
        while (($csv_array = fgetcsv ($handle)) !== FALSE ) {

            // Ausgeben des Arrays $csv_array
            foreach ($csv_array as $index) {

                $tmp = explode(';', $index);



                foreach ($tmp as $key => $value) {
                    $value = trim($value);
                    $tmp[$key] = trim($tmp[$key]);

                    if (empty($value)) {
                        unset($tmp[$key]);
                        continue;
                    }

                    $tmp[$key] = utf8_encode($tmp[$key]);

                }


                if (count($tmp) <= 1) {
                    continue;
                }


                $arr[] = $tmp;


            }
        }

        // Datei schließen
        fclose($handle);

        $output->writeln('synonym groups found: ' . count($arr));


        $output->writeln('clearing attribute ' . $this->configuration['synonymAttribute']);




        $output->writeln('reading articles');


        $add = array();


        foreach ($arr as $group) {



            foreach ($group as $synonym) {


                $query = '
                    SELECT id
                    FROM s_articles
                    WHERE name LIKE :name
                ';
                $ids = array_column(Shopware()->Db()->fetchAll($query, array('name' => '%' . $synonym . '%')), 'id');






                foreach ($ids as $id) {

                    if (!isset($add[$id])) {
                        $add[$id] = array();
                    }





                    $add[$id]= array_merge($add[$id], $group);




                    $add[$id] = array_unique($add[$id]);


                }





            }



        }



        $output->writeln('articles found to update: ' . count($add));


        foreach ($add as $id => $synonyms) {


            $query = '
            UPDATE s_articles_attributes
            SET attr24 = :synonyms
            WHERE articleID = :id
            ';

            Shopware()->Db()->query(
                $query,
                array(
                    'synonyms' => implode(' ', $synonyms),
                    'id' => $id
                )
            );



        }



        $output->writeln('ende');
    }
}
