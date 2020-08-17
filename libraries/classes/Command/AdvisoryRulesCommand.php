<?php
/**
 * Translates advisory rules to Gettext format
 */

declare(strict_types=1);

namespace PhpMyAdmin\Command;

use PhpMyAdmin\Advisor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function addcslashes;
use function array_search;
use function implode;
use function strstr;

/**
 * Translates advisory rules to Gettext format
 */
class AdvisoryRulesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'po:advisory-rules';

    /** @var array */
    private $messages = [];

    /** @var array */
    private $locations = [];

    protected function configure(): void
    {
        $this->setDescription('Translates advisory rules to Gettext format');
        $this->setHelp(
            'This command parses advisory rules and output them'
            . ' as Gettext POT formatted strings for translation.'
        );
    }

    /**
     * @param InputInterface  $input  input
     * @param OutputInterface $output output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ruleFiles = [];
        $ruleFiles[Advisor::GENERIC_RULES_FILE] = Advisor::parseRulesFile(
            Advisor::GENERIC_RULES_FILE
        );
        $ruleFiles[Advisor::BEFORE_MYSQL80003_RULES_FILE] = Advisor::parseRulesFile(
            Advisor::BEFORE_MYSQL80003_RULES_FILE
        );

        foreach ($ruleFiles as $file => $rules) {
            foreach ($rules['rules'] as $idx => $rule) {
                $this->addMessage($file, $rules, $idx, 'name');
                $this->addMessage($file, $rules, $idx, 'issue');
                $this->addMessage($file, $rules, $idx, 'recommendation');
                $this->addMessage($file, $rules, $idx, 'justification');
            }
        }

        foreach ($this->messages as $index => $message) {
            $output->writeln('');
            $output->write('#: ');
            $output->writeln(implode(' ', $this->locations[$index]));
            if (strstr($this->messages[$index], '%') !== false) {
                $output->writeln('#, php-format');
            }
            $output->write('msgid "');
            $output->write(addcslashes(
                Advisor::escapePercent($this->messages[$index]),
                '"\\'
            ));
            $output->writeln('"');
            $output->writeln('msgstr ""');
        }

        return 0;
    }

    /**
     * @param string $file  file name
     * @param array  $rules rules array
     * @param int    $index rule index
     * @param string $type  rule type
     */
    private function addMessage(string $file, array $rules, int $index, string $type): void
    {
        if ($type === 'justification') {
            $messages = Advisor::splitJustification($rules['rules'][$index]);
            $message = $messages[0];
        } else {
            $message = $rules['rules'][$index][$type];
        }
        $line = $file . ':' . $rules['lines'][$index][$type];

        $pos = array_search($message, $this->messages);
        if ($pos === false) {
            $this->messages[] = $message;
            $this->locations[] = [$line];
        } else {
            $this->locations[$pos][] = $line;
        }
    }
}
