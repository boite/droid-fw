<?php

namespace Droid\Plugin\Fw\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Droid\Model\Inventory;
use Droid\Plugin\Fw\Model\Firewall;
use Droid\Plugin\Fw\Loader\YamlLoader;
use RuntimeException;

class FwGenerateCommand extends Command
{
    public function configure()
    {
        $this->setName('fw:generate')
            ->setDescription('Generate firewall for given host')
            ->addArgument(
                'hostname',
                InputArgument::REQUIRED,
                'Name of the host to generate the firewall for'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'YAML config file with firewall rules'
            )
        ;
    }
    
    protected $inventory;
    
    public function setInventory(Inventory $inventory)
    {
        $this->inventory = $inventory;
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $input->getOption('config');
        $hostname = $input->getArgument('hostname');
        if (!$config) {
            throw new RuntimeException("Firewall config not defined (use --config).");
        }
        $output->writeLn("Generating firewall for: " . $input->getArgument('hostname'));
        if (!$this->inventory) {
            throw new RuntimeException("Inventory not defined.");
        }
        
        $firewall = new Firewall($this->inventory);
        $loader = new YamlLoader();
        $loader->load($firewall, $config);
        
        $rules = $firewall->getRulesByHostname($hostname);
        print_r($rules);
    }
}
