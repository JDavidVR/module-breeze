<?php

namespace Swissup\Breeze\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Swissup\Breeze\Model\BreezeThemesProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestionFactory;
use Symfony\Component\Console\Question\ConfirmationQuestionFactory;
use Symfony\Component\Console\Question\QuestionFactory;

class ThemeCreateCommand extends Command
{
    /** @var Stubs */
    protected $stubs;

    /** @var ConfirmationQuestionFactory */
    protected $confirmationQuestionFactory;

    /** @var QuestionFactory */
    protected $questionFactory;

    /** @var ChoiceQuestionFactory */
    protected $choiceQuestionFactory;

    /** @var QuestionHelper */
    protected $questionHelper;

    /** @var BreezeThemesProvider */
    protected $breezeThemesProvider;

    /** @var InputInterface */
    protected $input;

    /** @var OutputInterface */
    protected $output;

    public function __construct(
        Stubs $stubs,
        Filesystem $filesystem,
        ConfirmationQuestionFactory $confirmationQuestionFactory,
        QuestionFactory $questionFactory,
        ChoiceQuestionFactory $choiceQuestionFactory,
        QuestionHelper $questionHelper,
        BreezeThemesProvider $breezeThemesProvider
    ) {
        $this->stubs = $stubs;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::APP);
        $this->confirmationQuestionFactory = $confirmationQuestionFactory;
        $this->questionFactory = $questionFactory;
        $this->choiceQuestionFactory = $choiceQuestionFactory;
        $this->questionHelper = $questionHelper;
        $this->breezeThemesProvider = $breezeThemesProvider;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('breeze:theme:create')
            ->setDescription('Create breeze theme')
            ->addArgument('package', InputArgument::OPTIONAL, 'Package name (vendor/theme-frontend-name)')
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent theme code [Swissup/breeze-blank]')
            ->addOption('vendor', null, InputOption::VALUE_REQUIRED, 'Vendor name');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        try {
            $parentTheme = $this->getParentTheme();
            $package = $this->getPackageName();

            if (!$this->input->getArgument('package')) {
                $confirm = $this->ask($this->confirmationQuestionFactory->create([
                    'question' => "Do you want to create {$package} theme? (y/n) [y]"
                ]));

                if (!$confirm) {
                    return;
                }
            }

            $paths = $this->create($package, $parentTheme);

            foreach ($paths as $path => $success) {
                $this->output->writeln(
                    $success ? "<info>✓ {$path}</info>" : "<error>✗ {$path}</error>"
                );
            }

            $output->writeln('<info>Done! Do not forget to activate your new theme from Content > Design > Configuration.</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln($e->getTraceAsString());
            }

            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }

        return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
    }

    private function getPackageName()
    {
        $package = $this->input->getArgument('package');
        if ($package) {
            return $package;
        }

        $vendor = $this->input->getOption('vendor');
        if (!$vendor) {
            if (!$vendor = $this->ask('Enter the vendor name: ')) {
                throw new \Exception('Vendor name is required. Use --vendor=name.');
            }
        }

        $theme = $this->ask('Enter the theme name: ');

        if (strpos($theme, 'theme-frontend-') === false) {
            $theme = 'theme-frontend-' . $theme;
        }

        return $vendor . '/' . $theme;
    }

    private function getParentTheme()
    {
        if (!$code = $this->input->getOption('parent')) {
            $code = $this->ask($this->choiceQuestionFactory->create([
                'question' => 'Select parent theme: ',
                'choices' => $this->getParentThemeChoices(),
            ]));
        }

        return $this->breezeThemesProvider->getTheme($code);
    }

    private function getParentThemeChoices()
    {
        $result = [];

        foreach ($this->breezeThemesProvider->getThemes() as $theme) {
            $result[$theme->getCode()] = $theme->getCode();
        }

        return $result;
    }

    private function create(string $package, $parentTheme)
    {
        $result = [];

        foreach ($this->stubs->theme($package, $parentTheme) as $path => $values) {
            if (!empty($values['skip']) && $this->input->getOption($values['skip'])) {
                continue;
            }

            $result[$path] = !$this->directory->isExist($path);
            if (!$result[$path]) {
                continue;
            }

            $this->directory->writeFile($path, $values['content']);
        }

        return $result;
    }

    private function ask($question, $validator = null)
    {
        if (is_string($question)) {
            $question = $this->questionFactory->create(['question' => $question]);

            if ($validator === null) {
                $validator = function ($value) {
                    if (!$value || trim($value) === '') {
                        throw new \Exception('Value cannot be empty');
                    }

                    return $value;
                };
            }
        }

        if ($validator) {
            $question->setValidator($validator);
        }

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}
