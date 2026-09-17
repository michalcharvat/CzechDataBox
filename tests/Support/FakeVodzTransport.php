<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Support;

use MichalCharvat\CzechDataBox\Credentials\PasswordCredentials;
use MichalCharvat\CzechDataBox\Transport\TransportOptions;
use MichalCharvat\CzechDataBox\Transport\Vodz\VodzTransport;

/** Replays tests/fixtures/vodz/<case>.xml as the parsed root part; an optional binary becomes part "fixture@cid". */
final class FakeVodzTransport extends VodzTransport
{
    /** @var array<string, list<array{string, ?string}>> */
    private array $queue = [];
    /** @var list<string> */
    public array $uploadedBinaries = [];
    /** @var list<string> */
    public array $operations = [];
    public string $lastBodyXml = '';

    public function __construct()
    {
        parent::__construct('https://replay.invalid/', new PasswordCredentials('u', 'p'), new TransportOptions());
    }

    public function reply(string $operation, string $case, ?string $binary = null): self
    {
        $this->queue[$operation][] = [$case, $binary];
        return $this;
    }

    public function call(string $operation, #[\SensitiveParameter] string $bodyXml, array $binaries, callable $sinkFactory): array
    {
        $this->operations[] = $operation;
        $this->lastBodyXml = $bodyXml;
        foreach ($binaries as [$stream]) {
            rewind($stream);
            $this->uploadedBinaries[] = (string)stream_get_contents($stream);
        }
        if (empty($this->queue[$operation])) {
            throw new \LogicException('No VoDZ fixture queued for ' . $operation);
        }
        [$case, $binary] = array_shift($this->queue[$operation]);
        $doc = new \DOMDocument();
        $doc->loadXML(Fixture::loadVodz($case));
        if ($binary === null) {
            return [$doc, []];
        }
        fwrite($sinkFactory('fixture@cid'), $binary);
        return [$doc, ['fixture@cid']];
    }
}
