<?php

/**
 * PHP version 5.4 and 7
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getModule()->isActive()) {
            $output->writeln('The payever module is being activated');
            // @codeCoverageIgnoreStart
            return;
        }
        $oModuleInstaller = null;
        if (class_exists('oxModuleInstaller')) {
            /** @var \oxModuleInstaller|null $oModuleInstaller */
            $oModuleInstaller = \oxNew(\oxModuleInstaller::class);
        }
        if ($oModuleInstaller) {
            if ($oModuleInstaller->activate($this->getModule())) {
                $output->writeln('The payever module is activated');
                return;
            }
            throw new \RuntimeException('Couldn\'t activate payever module');
        } elseif (method_exists($this->getModule(), 'activate')) {
            // old oxid versions
            $this->getModule()->activate();
            return;
        }
        throw new \RuntimeException('Unable to activate payever module');
        // @codeCoverageIgnoreEnd
    }
}
