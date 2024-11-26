<?php

namespace Athenea\MonologBundle\Email;


use Symfony\Component\Mime\Email;


class ErrorMailPrototype extends Email
{
    /**
     * @param string[] $toArray
     */
    public function setDefaults(
        array $toArray,
        string $from,
        string $env,
        string $appName
    ): void {
        $envName = $env === 'dev' ? 'QAS' : ($env === 'prod' ? 'PROD' : 'unknown');
        $this->to(...$toArray);
        $this->from($from);
        $this->subject("[$appName-$envName] %message%");
    }
}