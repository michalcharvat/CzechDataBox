<?php

declare(strict_types=1);

namespace MichalCharvat\CzechDataBox\Tests\Live;

use MichalCharvat\CzechDataBox\Input\ListFilter;
use MichalCharvat\CzechDataBox\Zfo\CmsUnwrapper;

/**
 * ISDS_CAPTURE=1: stores real signed documents in tests/fixtures/captured/ (git-ignored) and their unwrapped
 * XML next to them, for comparing the real structure with ZfoFactory / ZfoXmlParser. Delivers the received message.
 */
final class ZfoCaptureLiveTest extends LiveTestCase
{
    public function testCaptureSignedDocuments(): void
    {
        self::env('ISDS_CAPTURE');
        $conn = $this->passwordConnection();
        $window = new ListFilter(new \DateTimeImmutable('-90 days'), new \DateTimeImmutable());
        $saved = [];

        foreach ($conn->messageInfo()->getListOfReceivedMessages($window) as $r) {
            $saved['received'] = $conn->messageOperations()->signedMessageDownload($r->dmID)->bytes;
            break;
        }
        foreach ($conn->messageInfo()->getListOfSentMessages($window) as $r) {
            if (($r->dmMessageStatus ?? 0) >= 4) {
                $saved['sent'] = $conn->messageOperations()->signedSentMessageDownload($r->dmID)->bytes;
                $saved['delivery'] = $conn->messageInfo()->getSignedDeliveryInfo($r->dmID)->bytes;
                break;
            }
        }
        if ($saved === []) {
            self::markTestSkipped('nothing to capture');
        }
        foreach ($saved as $name => $bytes) {
            file_put_contents(self::capturedPath($name . '.zfo'), $bytes);
            file_put_contents(self::capturedPath($name . '.xml'), (new CmsUnwrapper())->content($bytes));
        }
        self::assertNotEmpty($saved);
    }
}
