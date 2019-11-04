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

namespace OstEnterpriseSearch\Listeners\SwagEnterpriseSearch;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use SwagEnterpriseSearch\Bundle\EnterpriseSearchBundle\SearchRedirect\SearchRedirectInterface;
use SwagEnterpriseSearch\Bundle\ESIndexingBundle\Synonym\SynonymStruct;

class ProductNumberRedirect implements SearchRedirectInterface
{
    /**
     * ...
     *
     * @var Connection
     */
    private $connection;

    /**
     * ...
     *
     * @var
     */
    private $router;

    /**
     * ...
     *
     * @var ContextServiceInterface
     */
    private $contextService;

    /**
     * The previously existing core service.
     *
     * @var SearchRedirectInterface
     */
    private $coreService;

    /**
     * @param SearchRedirectInterface $coreService
     * @param Connection              $connection
     * @param                         $router
     * @param ContextServiceInterface $contextService
     */
    public function __construct(SearchRedirectInterface $coreService, Connection $connection, $router, ContextServiceInterface $contextService)
    {
        $this->coreService = $coreService;
        $this->connection = $connection;
        $this->router = $router;
        $this->contextService = $contextService;
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirect($searchTerm, SynonymStruct $synonym = null)
    {
        $result = $this->getArticleByNumber($searchTerm);

        if (!$result) {
            return;
        }

        $assembleParams = [
            'sViewport' => 'detail',
            'sArticle' => $result['articleId'],
        ];

        // if variant is not the main variant, add the number to the URL
        if ($result['kind'] != 1) {
            $assembleParams['number'] = $result['number'];
        }

        return $this->router->assemble($assembleParams);
    }

    /**
     * ...
     *
     * @param $searchTerm
     *
     * @return array
     */
    protected function getArticleByNumber($searchTerm)
    {
        $result = $this->connection->fetchAll(
            'SELECT
              ad.ordernumber as number,
              ad.articleID as articleId,
              ad.kind
            FROM s_articles_details ad

            INNER JOIN s_categories c
              ON c.id = :mainCategory
              AND c.active = 1

            INNER JOIN s_articles_categories_ro ro
              ON ro.articleID = ad.articleID
              AND ro.categoryID = c.id


            WHERE `ordernumber` LIKE :number
                OR `ordernumber` LIKE :variantNumber
            LIMIT 1',
            ['number' => $searchTerm, 'variantNumber' => $searchTerm . '-1', 'mainCategory' => $this->contextService->getShopContext()->getShop()->getCategory(
            )->getId()]
        );

        return array_shift($result);
    }
}
