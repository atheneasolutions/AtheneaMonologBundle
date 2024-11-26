<?php


namespace Athenea\MonologBundle\LogHandler;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\MongoDBFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

class MongoLogHandler  extends AbstractProcessingHandler
{
    
    private readonly \MongoDB\Collection $collection;
    /** @var Client|Manager */
    private $manager;
    /** @var string */
    private $namespace;

    /**
     * Constructor.
     *
     * @param Client|Manager $mongodb    MongoDB library or driver client
     * @param string         $database   Database name
     * @param string         $collection Collection name
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly LoggerInterface $logger,
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

    /**
     * {@inheritDoc}
     */
    public function handleBatch(array $records): void
    {
        $messages = [];
        $firstCritical = null;

        foreach ($records as $record) {
            $message = $this->processRecord($record);
            if(($message->level?? null) === Level::Critical) $firstCritical = $message;
            $messages[] = $message;
        }

        $formated = $this->getFormatter()->formatBatch($messages);
        if($firstCritical) $firstCritical = $this->getFormatter()->format($firstCritical);
        else return;
        $datetime = new UTCDateTime();
        $this->collection->insertOne(['grouped' => true, 'formated' => $formated, 'first_critical' => $firstCritical, 'datetime' => $datetime ]);
    }

    protected function write(LogRecord $record): void
    {
        $formated = $this->getFormatter()->format($record);
        $formated['grouped'] = false;
        $this->collection->insertOne($formated);
    }


    /**
     * {@inheritDoc}
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new MongoDBFormatter();
    }

}
