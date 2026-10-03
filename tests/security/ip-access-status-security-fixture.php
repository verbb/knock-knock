<?php

declare(strict_types=1);

namespace craft\web {
    class Request
    {
    }
}

namespace yii\validators {
    class IpValidator
    {
    }
}

namespace verbb\knockknock {
    class KnockKnock
    {
        public static mixed $plugin = null;
    }
}

namespace {
    use verbb\knockknock\helpers\IpHelper;

    class Craft
    {
        public static object $app;
    }

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    require dirname(__DIR__, 2) . '/src/helpers/IpHelper.php';

    assertSame(IpHelper::ACCESS_ALLOWED, IpHelper::getAccessStatus('203.0.113.10', ['203.0.113.10'], []), 'An allow rule must produce the allowed status.');
    assertSame(IpHelper::ACCESS_DENIED, IpHelper::getAccessStatus('203.0.113.10', [], ['203.0.113.10']), 'A deny rule must produce the denied status.');
    assertSame(IpHelper::ACCESS_ALLOWED, IpHelper::getAccessStatus('203.0.113.10', ['203.0.113.10'], ['203.0.113.10']), 'An allow rule must take precedence over a deny rule.');
    assertSame(IpHelper::ACCESS_NEUTRAL, IpHelper::getAccessStatus('203.0.113.10', ['198.51.100.0/24'], ['192.0.2.0/24']), 'An unmatched IP must produce the neutral status.');
    assertSame(IpHelper::ACCESS_DENIED, IpHelper::getAccessStatus('203.0.113.10', [], ['203.0.113.0/24']), 'Deny CIDR rules must use the existing matcher.');

    echo "Knock Knock IP access status security fixture passed.\n";
}
