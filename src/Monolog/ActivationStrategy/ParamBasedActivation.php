<?php


namespace Athenea\MonologBundle\Monolog\ActivationStrategy;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Level;
use Monolog\LogRecord;

class ParamBasedActivation implements ActivationStrategyInterface
{
    private bool $isMailLogsEnabled;

    public function __construct(bool $enabled)
    {
        $this->isMailLogsEnabled = $enabled;
    }

    public function isHandlerActivated(LogRecord $record): bool
    {
        return $this->isMailLogsEnabled && !$record->level->isLowerThan(Level::Critical);
    }

}
