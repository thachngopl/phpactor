<?php

namespace Phpactor\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Phpactor\Application\ClassMover;
use Symfony\Component\Console\Input\InputArgument;
use Phpactor\Console\Logger\SymfonyConsoleMoveLogger;
use Symfony\Component\Console\Input\InputOption;
use Phpactor\Console\Prompt\Prompt;
use Phpactor\Container\SourceCodeFilesystemExtension;

class ClassMoveCommand extends Command
{
    const TYPE_AUTO = 'auto';
    const TYPE_CLASS = 'class';
    const TYPE_FILE = 'file';

    /**
     * @var ClassMover
     */
    private $mover;

    /**
     * @var Prompt
     */
    private $prompt;

    public function __construct(
        ClassMover $mover,
        Prompt $prompt
    ) {
        parent::__construct();
        $this->mover = $mover;
        $this->prompt = $prompt;
    }

    public function configure()
    {
        $this->setName('class:move');
        $this->setDescription('Move class (path or FQN) and update all references to it');
        $this->addArgument('src', InputArgument::REQUIRED, 'Source path or FQN');
        $this->addArgument('dest', InputArgument::OPTIONAL, 'Destination path or FQN');
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, sprintf(
            'Type of move: "%s"',
             implode('", "', [self::TYPE_AUTO, self::TYPE_CLASS, self::TYPE_FILE])
        ), self::TYPE_AUTO);
        Handler\FilesystemHandler::configure($this, SourceCodeFilesystemExtension::FILESYSTEM_GIT);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption('type');
        $logger = new SymfonyConsoleMoveLogger($output);
        $src = $input->getArgument('src');
        $dest = $input->getArgument('dest');
        $filesystem = $input->getOption('filesystem');

        if (null === $dest) {
            $dest = $this->prompt->prompt('Move to: ', $src);
        }

        switch ($type) {
            case 'auto':
                return $this->mover->move($logger, $filesystem, $src, $dest);
            case 'file':
                return $this->mover->moveFile($logger, $filesystem, $src, $dest);
            case 'class':
                return $this->mover->moveClass($logger, $filesystem, $src, $dest);
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid type "%s", must be one of: "%s"',
            $type,
            implode('", "', [ self::TYPE_AUTO, self::TYPE_FILE, self::TYPE_CLASS ])
        ));
    }
}
