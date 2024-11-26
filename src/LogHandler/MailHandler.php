<?php

namespace Athenea\MonologBundle\LogHandler;

use Athenea\MonologBundle\Email\ErrorMailPrototype;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;


final class MailHandler extends AbstractProcessingHandler
{

    // Content string in max characters. Currently 5MB
    const MAX_SIZE = 5000000;
    private MailerInterface $mailer;
    private \Closure|Email $messageTemplate;

    public function __construct(
        private LoggerInterface $logger,
        MailerInterface $mailer,
        string $env,
        array|string $emailRecipients,
        string $emailFrom,
        string $appName,
        string|int|Level $level = Level::Debug,
        bool $bubble = true
    )
    {
        $emailRecipients = $this->validateRecipients($emailRecipients);
        parent::__construct($level, $bubble);
        $this->messageTemplate = new ErrorMailPrototype();
        $this->messageTemplate->setDefaults(
            toArray: $emailRecipients,
            from: $emailFrom,
            env: $env,
            appName: $appName
        );
        $this->mailer = $mailer;

        $this->setFormatter(new HtmlFormatter());
        // Add the PsrLogMessageProcessor for context substitution
        $this->pushProcessor(new PsrLogMessageProcessor());
    }

    public function validateRecipients(array|string $recipients): array
    {
        if (is_string($recipients)) {
            $recipientsArray = array_map('trim', explode(',', $recipients));
        } elseif (is_array($recipients)) {
            $recipientsArray = $recipients;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Invalid type for "email_recipients". Expected string or array, got "%s".',
                gettype($recipients)
            ));
        }

        foreach ($recipientsArray as $recipient) {
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid email address: "%s".',
                    $recipient
                ));
            }
        }

        return $recipientsArray;
    }

    public function handleBatch(array $records): void
    {
        $messages = [];
        $anyCritical = false;
        foreach ($records as $record) {
            if (! $record->level->isLowerThan(Level::Critical)) {
                $anyCritical = true;
            }
            $messages[] = $this->processRecord($record);
        }
        if(!$anyCritical){
            return;
        }

        if (count($messages) > 0) {
            $content = (string) $this->getFormatter()->formatBatch($messages);
            // if content is bigger than MAX_SIZE reformat with only error
            if (strlen($content) > self::MAX_SIZE) {
                $errorMessages = array_filter($messages, fn(LogRecord $record) => !$record->level->isLowerThan(Level::Error));
                $content = (string) $this->getFormatter()->formatBatch($errorMessages);
                
                // if yet again is too much reformat with only critical
                if (strlen($content) > self::MAX_SIZE) {
                    $criticalMessages = array_filter($messages, fn(LogRecord $record) => !$record->level->isLowerThan(Level::Critical));
                    $content = (string) $this->getFormatter()->formatBatch($criticalMessages);

                    // if yet again is too much, the content will only contain the highest record without context
                    if (strlen($content) > self::MAX_SIZE) {
                        $highestRecord = $this->getHighestRecord($records);
                        $highestRecord = new LogRecord(
                            datetime: $highestRecord->datetime,
                            channel: $highestRecord->channel,
                            level: $highestRecord->level,
                            message: $highestRecord->message,
                        );
                        $content = (string) $this->getFormatter()->formatBatch([$highestRecord]);
                    }
                }
            }
            $this->send($content, $messages);
        }
    }

    protected function write(LogRecord $record): void
    {
        $this->send((string) $record->formatted, [$record]);
    }

    /**
     * Send a mail with the given content.
     *
     * @param string $content formatted email body to be sent
     * @param array  $records the array of log records that formed this content
     */
    protected function send(string $content, array $records): void
    {
        $this->mailer->send($this->buildMessage($content, $records));
    }

    /**
     * Gets the formatter for the Message subject.
     *
     * @param string $format The format of the subject
     */
    protected function getSubjectFormatter(string $format): FormatterInterface
    {
        return new LineFormatter($format);
    }

    /**
     * Creates instance of Message to be sent.
     *
     * @param string $content formatted email body to be sent
     * @param array  $records Log records that formed the content
     */
    protected function buildMessage(string $content, array $records): Email
    {
        if ($this->messageTemplate instanceof Email) {
            $message = clone $this->messageTemplate;
        } elseif (\is_callable($this->messageTemplate)) {
            $message = ($this->messageTemplate)($content, $records);
            if (!$message instanceof Email) {
                throw new \InvalidArgumentException(sprintf('Could not resolve message from a callable. Instance of "%s" is expected.', Email::class));
            }
        } else {
            throw new \InvalidArgumentException('Could not resolve message as instance of Email or a callable returning it.');
        }

        if ($records) {
            $subjectFormatter = $this->getSubjectFormatter($message->getSubject());
            $message->subject($subjectFormatter->format($this->getHighestRecord($records)));
        }

        if ($message->getHtmlCharset()) {
            $message->html($content, $message->getHtmlCharset());
        } else {
            $message->html($content);
        }
        return $message;
    }

    protected function getHighestRecord(array $records): LogRecord
    {
        $highestRecord = null;
        foreach ($records as $record) {
            if (null === $highestRecord || $highestRecord->level->isLowerThan($record->level)) {
                $highestRecord = $record;
            }
        }

        return $highestRecord;
    }

}
