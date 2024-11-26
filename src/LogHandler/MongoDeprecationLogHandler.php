<?php

namespace Athenea\MonologBundle\LogHandler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\MongoDBFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;

class MongoDeprecationLogHandler extends AbstractProcessingHandler
{
    private readonly \MongoDB\Collection $collection;

    public function __construct(
        private readonly DocumentManager $dm,
        private string $collectionName,
        $level = Level::Debug,
        bool $bubble = true    
    )
    {
        $this->pushProcessor(new PsrLogMessageProcessor());
        $client = $dm->getClient();
        $database = $dm->getConfiguration()->getDefaultDB();
        $this->collection = $client->selectCollection($database, $this->collectionName);
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        // Format and store the log record in the "deprecations" collection
        $formatted = $this->getFormatter()->format($record);
        $formatted['grouped'] = false; // To keep it consistent with your other logs
        $this->collection->insertOne($formatted);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new MongoDBFormatter();
    }
}
