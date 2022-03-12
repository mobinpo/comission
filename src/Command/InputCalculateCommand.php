<?php

namespace App\Command;

use App\Exception\CardInfosException;
use App\Exception\RatesAcessKeyException;
use App\Exception\RatesAPIException;
use App\Service\Card;
use App\Service\Transactions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:input-calculate',
    description: 'Calculate input commissions',
)]
class InputCalculateCommand extends Command
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::REQUIRED, 'txt input file name')
        ;
    }

    /**
     * @throws RatesAcessKeyException
     * @throws CardInfosException
     * @throws RatesAPIException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        $io->success('calculation is completed successfully! Pass --help to see your options.');

        $card = new Card($this->params);
        $transactions = new Transactions($card);
        $transactions->setFile($arg1);
        $result = $transactions->handle();
        foreach ($result as $value) {
            echo $value;
            print "\n";
        }
        return Command::SUCCESS;
    }

}
