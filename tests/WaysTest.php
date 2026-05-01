<?php

declare(strict_types=1);

namespace divengine\tests;

use divengine\ways;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class WaysTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetWaysState();
        $_GET = [];
        $_SERVER['argv'] = ['phpunit'];
        $_SERVER['argc'] = 1;
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_SOFTWARE']);
    }

    public function testMatchesArgumentsInWays(): void
    {
        $args = [];

        self::assertTrue(ways::match('/users/{id|is_int}', '/users/42', $args));
        self::assertSame(['id' => '42'], $args);
    }

    public function testInvokesARegisteredClosureAndReturnsData(): void
    {
        ways::listen('get://hello/{name}', function (array $data, array $args): array {
            return [
                'message' => "Hello {$args['name']}",
                'seed' => $data['seed'],
            ];
        });

        $result = ways::invoke('get://hello/Ada', ['seed' => 'ways']);

        self::assertSame('Hello Ada', $result['message']);
        self::assertSame('ways', $result['seed']);
        self::assertSame(1, ways::getTotalExecutions());
    }

    public function testBeforeRunHookCanUpdateControllerData(): void
    {
        ways::listen('/home', function (array $data): array {
            return ['user' => $data['user']];
        }, 'home');

        ways::hook(DIV_WAYS_BEFORE_RUN, 'home', function (array $data): array {
            $data['user'] = 'Peter';
            return $data;
        });

        $result = ways::invoke('/home');

        self::assertSame('Peter', $result['user']);
    }

    public function testRuleCanPreventControllerExecution(): void
    {
        ways::rule('deny', static fn (): bool => false);

        ways::listen('/secret', function (): array {
            return ['executed' => true];
        }, [
            ways::PROPERTY_ID => 'secret',
            ways::PROPERTY_RULES => ['deny'],
        ]);

        $result = ways::invoke('/secret');

        self::assertArrayNotHasKey('executed', $result);
        self::assertSame(0, ways::getTotalExecutions());
    }

    private function resetWaysState(): void
    {
        $class = new ReflectionClass(ways::class);
        $defaults = $class->getDefaultProperties();

        foreach ($class->getProperties() as $property) {
            if (!$property->isStatic()) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue(null, $defaults[$property->getName()] ?? null);
        }
    }
}
