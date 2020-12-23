<?php

namespace App\Command;

use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    /**
     * @var EntityManagerInterface
     */
    private $manager;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(EntityManagerInterface $manager,UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->manager = $manager;
        $this->encoder= $passwordEncoder;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Tworzenie nowego konta użytkownika')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $output->writeln([
            ' API User Creator',
            ' ============',
            '',
        ]);
        $question=new Question("Podaj nazwę użytkownika");
        $name=$io->askQuestion($question);
        $question=new Question("Wprowadź hasło użytkownika");
        $plainPass=$io->askQuestion($question);
        $apiUser=new User();
        $apiUser->setUsername($name)->setPassword($this->encoder->encodePassword(
            $apiUser,
            $plainPass
        ));
        $date=new DateTime();
        $apiUser->setToken($key=md5($apiUser->getUsername().$date->format('YmdHis')));
        $this->manager->persist($apiUser);
        $this->manager->flush();

        $io->success("Stworzono nowego użytkownika: {$apiUser->getUsername()}, jego ApiToken to : {$apiUser->getToken()}");

        return 0;
    }
}
