<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 */
class payeverShowLogs extends oxUBase
{
    use PayeverConfigHelperTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverRequestHelperTrait;

    /**
     * @return void
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function render()
    {
        $apiClient = $this->getThirdPartyPluginsApiClient();

        try {
            if (
                !$apiClient->validateToken(
                    $this->getConfigHelper()->getBusinessUuid(),
                    $this->getRequestHelper()->getHeader('Authorization')
                )
            ) {
                throw new \Exception('Access denied.');
            }
        } catch (\Exception $exception) {
            header('Content-Type: application/json');
            header('401 Unauthorized');

            //@codeCoverageIgnoreStart
            echo json_encode(['error' => $exception->getMessage()]);
            exit;
            //@codeCoverageIgnoreEnd
        }

        $fromDate = $this->getRequestHelper()->getStringParam('fromDate');
        if (!$fromDate) {
            $fromDate = date('Y-m-d');
        }
        $toDate = $this->getRequestHelper()->getStringParam('toDate');
        if (!$toDate) {
            $toDate = date('Y-m-d');
        }

        $logsListFactory = new PayeverLogsListFactory();
        $collection = $logsListFactory->create();
        $collection->clear();

        $logsFactory = new PayeverLogsFactory();
        $logsObject = $logsFactory->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($logsObject);

        $query = $logsObject->buildSelectString(null);
        $query .= ' ORDER BY `log_id`';
        $collection->selectString($query);

        $result = [];
        $items = $collection->getArray();
        foreach ($items as $item) {
            /** @var payeverlogs $item */
            $data = html_entity_decode($item->getFieldData('data'));
            $createdAt = $item->getFieldData('created_at');

            if (
                (new DateTime($createdAt)) > (new DateTime(date('Y-m-d 00:00:00', strtotime($fromDate)))) &&
                (new DateTime($createdAt)) < (new DateTime(date('Y-m-d 23:59:59', strtotime($toDate))))
            ) {
                $context = \json_decode($data, true);
                if (\json_last_error() !== JSON_ERROR_NONE) {
                    $context = $data;
                }

                $result[] = [
                    'level' => $item->getFieldData('level'),
                    'message' => $item->getFieldData('message'),
                    'data' => $context,
                    'created_at' => $createdAt
                ];
            }
        }

        header('Content-Type: application/json');
        header('HTTP/1.1 200');

        //@codeCoverageIgnoreStart
        echo \json_encode($result);
        exit;
        //@codeCoverageIgnoreEnd
    }
}
