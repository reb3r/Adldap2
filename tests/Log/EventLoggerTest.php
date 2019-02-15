<?php

namespace Adldap\Tests\Log;

use Adldap\Models\User;
use Adldap\Tests\TestCase;
use Adldap\Log\EventLogger;
use Adldap\Auth\Events\Event as AuthEvent;
use Adldap\Models\Events\Event as ModelEvent;
use Adldap\Connections\ConnectionInterface;
use Psr\Log\LoggerInterface;

class EventLoggerTest extends TestCase
{
    public function test_auth_events_are_logged()
    {
        $e = $this->mock(AuthEvent::class);
        $l = $this->mock(LoggerInterface::class);
        $c = $this->mock(ConnectionInterface::class);

        $log = 'LDAP (ldap://192.168.1.1) - Operation: Mockery_4_Adldap_Auth_Events_Event - Username: jdoe@acme.org';

        $l->shouldReceive('info')->once()->with($log);

        $c
            ->shouldReceive('getHost')->once()->andReturn('ldap://192.168.1.1')
            ->shouldReceive('getLastError')->once()->andReturn('Success');

        $e
            ->shouldReceive('getConnection')->twice()->andReturn($c)
            ->shouldReceive('getUsername')->once()->andReturn('jdoe@acme.org');

        $eLogger = new EventLogger($l);

        $this->assertNUll($eLogger->auth($e));
    }

    public function test_model_events_are_logged()
    {
        $c = $this->mock(ConnectionInterface::class);

        $b = $this->newBuilder($c);

        $dn = 'cn=John Doe,dc=corp,dc=acme,dc=org';

        $u = new User(['dn' => $dn], $b);

        $l = $this->mock(LoggerInterface::class);

        $eLogger = new EventLogger($l);

        $me = $this->mock(ModelEvent::class);

        $me->shouldReceive('getModel')->once()->andReturn($u);

        $log = "LDAP (ldap://192.168.1.1) - Operation: Mockery_6_Adldap_Models_Events_Event - On: Adldap\Models\User - Distinguished Name: $dn";

        $l->shouldReceive('info')->once()->with($log);

        $c->shouldReceive('getHost')->once()->andReturn('ldap://192.168.1.1');

        $this->assertNUll($eLogger->model($me));
    }
}