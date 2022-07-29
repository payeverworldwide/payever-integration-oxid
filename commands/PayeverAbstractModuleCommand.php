<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

abstract class PayeverAbstractModuleCommand extends \Symfony\Component\Console\Command\Command
{
    use PayeverConfigTrait;

    const PAYEVER_MODULE_CODE = 'payever';

    /** @var oxModule */
    protected $module;

    /**
     * @param oxModule $module
     * @return $this
     */
    public function setModule(oxModule $module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return oxModule
     * @throws oxSystemComponentException
     * @codeCoverageIgnore
     */
    protected function getModule()
    {
        if (null === $this->module) {
            $this->module = \oxNew('oxModule');
            if (!$this->module->load(self::PAYEVER_MODULE_CODE)) {
                throw new \UnexpectedValueException('Couldn\'t load payever module');
            }
        }

        return $this->module;
    }
}
