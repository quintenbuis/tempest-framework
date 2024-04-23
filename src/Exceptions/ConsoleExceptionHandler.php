<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Tempest\Application;
use Tempest\Console\Console;
use Tempest\Console\ConsoleApplication;
use Tempest\Console\ConsoleOutput;
use Tempest\ExceptionHandler;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Themes\LightTerminalTheme;
use Throwable;

final readonly class ConsoleExceptionHandler implements ExceptionHandler
{
    public function __construct(
        private Console $console,
        private Application&ConsoleApplication $application,
    ) {
    }

    public function handle(Throwable $throwable): void
    {
        $this->console
            ->error($throwable::class)
            ->when(
                expression: $throwable->getMessage(),
                callback: fn (ConsoleOutput $output) => $output->error($throwable->getMessage()),
            )
            ->writeln();

        $this->writeSnippet($throwable);

        if ($this->application->argumentBag->get('-v')) {
            $this->console->writeln();

            foreach ($throwable->getTrace() as $i => $trace) {
                $this->console->writeln("#{$i} " . $this->formatTrace($trace));
            }

            $this->console->writeln();
        } else {
            $this->console
                ->writeln()
                ->writeln('<u>' . $throwable->getFile() . ':' . $throwable->getLine() . '</u>')
                ->writeln();
        }
    }

    private function writeSnippet(Throwable $throwable): void
    {
        $this->console->writeln($this->getCodeSample($throwable));
    }

    private function getCodeSample(Throwable $throwable): string
    {
        $highlighter = (new Highlighter(new LightTerminalTheme()))->withGutter();
        $code = $highlighter->parse(file_get_contents($throwable->getFile()), 'php');
        $lines = explode(PHP_EOL, $code);

        $lines[$throwable->getLine() - 1] = $lines[$throwable->getLine() - 1] . ' <error><</error>';

        $excerptSize = 5;
        $start = max(0, $throwable->getLine() - $excerptSize);
        $lines = array_slice($lines, $start, $excerptSize * 2);

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param mixed $trace
     * @return string
     */
    public function formatTrace(mixed $trace): string
    {
        if (isset($trace['file'])) {
            return '<u>' . $trace['file'] . ':' . $trace['line'] . '</u>';
        }

        if (isset($trace['class'])) {
            return $trace['class'] . $trace['type'] . $trace['function'];
        }

        return $trace['function'] . '()';
    }
}
