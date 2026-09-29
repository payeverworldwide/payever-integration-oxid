<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PayeverModuleActivateCommand extends PayeverAbstractModuleCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('payever:module:activate');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->getModule()->isActive()) {
            $output->writeln('The payever module is being activated');
            // @codeCoverageIgnoreStart
            return 0;
        }
        $oModuleInstaller = null;
        if (class_exists('oxModuleInstaller')) {
            /** @var \oxModuleInstaller|null $oModuleInstaller */
            $oModuleInstaller = \oxNew(\oxModuleInstaller::class);
        }
        if ($oModuleInstaller) {
            if ($oModuleInstaller->activate($this->getModule())) {
                $output->writeln('The payever module is activated');
                return 0;
            }
            throw new \BadMethodCallException('Couldn\'t activate payever module');
        } elseif (method_exists($this->getModule(), 'activate')) {
            // old oxid versions
            $this->getModule()->activate();
            return 0;
        }
        throw new \BadMethodCallException('Unable to activate payever module');
        // @codeCoverageIgnoreEnd
    }
}
