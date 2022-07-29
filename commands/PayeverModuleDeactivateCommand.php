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

class PayeverModuleDeactivateCommand extends PayeverAbstractModuleCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('payever:module:deactivate');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getModule()->isActive()) {
            $output->writeln('The payever module is being deactivated');
            // @codeCoverageIgnoreStart
            return;
        }
        $oModuleInstaller = null;
        if (class_exists('oxModuleInstaller')) {
            /** @var \oxModuleInstaller|null $oModuleInstaller */
            $oModuleInstaller = \oxNew(\oxModuleInstaller::class);
        }
        if ($oModuleInstaller) {
            if ($oModuleInstaller->deactivate($this->getModule())) {
                $output->writeln('The payever module is deactivated');
                return;
            }
            throw new \RuntimeException('Couldn\'t deactivate payever module');
        } elseif (method_exists($this->getModule(), 'deactivate')) {
            // old oxid versions
            $this->getModule()->deactivate();
            return;
        }
        throw new \RuntimeException('Unable deto activate payever module');
        // @codeCoverageIgnoreEnd
    }
}
