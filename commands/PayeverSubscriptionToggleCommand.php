<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PayeverSubscriptionToggleCommand extends PayeverAbstractModuleCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('payever:subscription:change')
            ->addArgument('status', InputArgument::REQUIRED);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $status = $input->getArgument('status');

        $subscriptionManager = new \PayeverSubscriptionManager();
        $subscriptionManager->setThirdPartyApiClient(\PayeverApiClientProvider::getThirdPartyApiClient(true));

        $result = $subscriptionManager->toggleSubscription($status === 'enable');
        $output->writeln($result ? 'enabled' : 'disabled');

        return 0;
    }
}
