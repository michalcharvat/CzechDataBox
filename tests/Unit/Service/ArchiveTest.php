<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Unit\Service;

use MichalCharvat\CzechDataBox\Service\Archive;
use MichalCharvat\CzechDataBox\Tests\Support\FakeVodzTransport;
use PHPUnit\Framework\TestCase;

final class ArchiveTest extends TestCase
{
    public function testArchiveReturnsDocumentAndNextStamp(): void
    {
        $t = (new FakeVodzTransport())->reply('ArchiveISDSDocument', 'archive-ok', binary: 'STAMPED-ZFO');
        $doc = (new Archive($t))->archiveIsdsDocument('ZFO');

        self::assertSame('STAMPED-ZFO', $doc->document?->bytes);
        self::assertSame('2031-03-01', $doc->nextStampTo?->format('Y-m-d'));
        self::assertSame('ZFO', $t->uploadedBinaries[0]);
        self::assertSame(['ArchiveISDSDocument'], $t->operations);
    }

    public function testArchiveStreamsIntoSink(): void
    {
        $t = (new FakeVodzTransport())->reply('ArchiveISDSDocument', 'archive-ok', binary: 'STAMPED-ZFO');
        $in = fopen('php://memory', 'w+b');
        fwrite($in, 'ZFO');
        $sink = fopen('php://memory', 'w+b');
        $doc = (new Archive($t))->archiveIsdsDocument($in, $sink);

        self::assertNull($doc->document);
        rewind($sink);
        self::assertSame('STAMPED-ZFO', stream_get_contents($sink));
    }
}
