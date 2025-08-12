<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverDisplayHelper
{
    use PayeverLoggerTrait;
    use PayeverFileLockTrait;
    use PayeverViewUtilTrait;
    use PayeverSessionTrait;

    /**
     * @param array|string $errors
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function addErrorToDisplay($errors)
    {
        $this->getSession()->deleteVariable('Errors');
        $oEx = new oxException();
        if (is_array($errors)) {
            $errors = array_unique($errors);
            foreach ($errors as $_error) {
                $oEx->setMessage($_error);
            }
        } else {
            $oEx->setMessage($errors);
        }

        $this->getViewUtil()->addErrorToDisplay($oEx);
    }
}
