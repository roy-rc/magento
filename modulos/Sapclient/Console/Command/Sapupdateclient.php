<?php


namespace Customcode\Sapclient\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sapupdateclient extends Command
{

    const NAME_ARGUMENT = "name";
    const NAME_OPTION = "option";

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        //$name = $input->getArgument(self::NAME_ARGUMENT);
        //$option = $input->getOption(self::NAME_OPTION);
        //$output->writeln("Hello " . $name."  ".$option);
        //bin/magento customcode:sap-update-client Roiman -a
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("customcode:sap-update-client");
        $this->setDescription("Update client from WS SAP");
        $this->setDefinition([
            new InputArgument(self::NAME_ARGUMENT, InputArgument::OPTIONAL, "Name"),
            new InputOption(self::NAME_OPTION, "-a", InputOption::VALUE_NONE, "Option functionality")
        ]);
        parent::configure();
    }
}


	