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

class PayeverModuleStatusCommand extends PayeverAbstractModuleCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('payever:module:status');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $active = $this->getModule()->isActive();
        $output->writeln($active ? 'active' : 'inactive');

        return 0;
    }
}
