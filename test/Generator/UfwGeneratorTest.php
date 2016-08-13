<?php

namespace Droid\Test\Plugin\Fw\Generator;

use Droid\Model\Feature\Firewall\Firewall;
use Droid\Model\Feature\Firewall\Rule;
use Droid\Model\Inventory\Inventory;

use Droid\Plugin\Fw\Generator\UfwGenerator;

class UfwGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $firewall;
    protected $inventory;

    protected function setUp()
    {
        $this->inventory = $this
            ->getMockBuilder(Inventory::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $this->firewall = $this
            ->getMockBuilder(Firewall::class)
            ->setConstructorArgs(array($this->inventory))
            ->getMock()
        ;
    }

    public function testICanLoadUfwGenerator()
    {
        new UfwGenerator($this->firewall);
    }

    public function testGenerateReturnsNullWhenThereAreZeroRules()
    {
        $this
            ->firewall
            ->method('getRulesByHostname')
            ->with('some-hostname')
            ->willReturn(array())
        ;
        $g = new UfwGenerator($this->firewall);
        $this->assertNull($g->generate('some-hostname'));
    }

    public function testGenerateReturnsWellFormedScript()
    {
        $rule = new Rule;
        $rule
            ->setAddress('0.0.0.0/0')
            ->setPort('53')
        ;

        $this
            ->firewall
            ->method('getRulesByHostname')
            ->with('some-hostname')
            ->willReturn(array($rule))
        ;
        $this
            ->firewall
            ->method('constructAddresses')
            ->willReturn(array($rule->getAddress()))
        ;

        $g = new UfwGenerator($this->firewall);
        $lines = explode("\n", $g->generate('some-hostname'));

        $this->assertCount(5, $lines);

        $this->assertStringStartsWith('# Generated by Droid', $lines[0]);
        $this->assertSame('ufw --force reset', $lines[1]);
        $this->assertStringStartsWith('ufw', $lines[2]);
        $this->assertSame('ufw --force enable', $lines[3]);
    }

    public function testGenerateRuleHandlesIngressRules()
    {
        $rule = new Rule;
        $rule
            ->setAddress('0.0.0.0/0')
            ->setPort('53')
            ->setProtocol('udp')
            ->setDirection('inbound')
            ->setAction('reject')
        ;

        $this
            ->firewall
            ->method('constructAddresses')
            ->willReturn(array($rule->getAddress()))
        ;

        $g = new UfwGenerator($this->firewall);

        $ruleCmds = $g->generateRule($rule);

        $this->assertStringStartsWith(
            'ufw reject proto udp from 0.0.0.0/0 to any port 53 #',
            $ruleCmds[0]
        );
    }

    public function testGenerateRuleHandlesIngressRulesWithMultipleAddresses()
    {
        $rule = new Rule;
        $rule
            ->setAddress('app_servers:private')
            ->setPort('53')
            ->setProtocol('udp')
            ->setDirection('inbound')
            ->setAction('reject')
        ;

        $this
            ->firewall
            ->method('constructAddresses')
            ->willReturn(
                array(
                    '192.0.2.1',
                    '192.0.2.2',
                )
            )
        ;

        $g = new UfwGenerator($this->firewall);

        $ruleCmds = $g->generateRule($rule);

        $this->assertCount(2, $ruleCmds);

        $this->assertStringStartsWith(
            'ufw reject proto udp from 192.0.2.1 to any port 53 #',
            $ruleCmds[0]
        );

        $this->assertStringStartsWith(
            'ufw reject proto udp from 192.0.2.2 to any port 53 #',
            $ruleCmds[1]
        );
    }

    public function testGenerateRuleHandlesEgressRules()
    {
        $rule = new Rule;
        $rule
            ->setAddress('0.0.0.0/0')
            ->setPort('53')
            ->setProtocol('udp')
            ->setDirection('outbound')
            ->setAction('reject')
        ;

        $this
            ->firewall
            ->method('constructAddresses')
            ->willReturn(array($rule->getAddress()))
        ;

        $g = new UfwGenerator($this->firewall);

        $ruleCmds = $g->generateRule($rule);

        $this->assertStringStartsWith(
            'ufw reject proto udp from any to 0.0.0.0/0 port 53 #',
            $ruleCmds[0]
        );
    }

    public function testGenerateRuleHandlesEgressRulesWithMultipleAddresses()
    {
        $rule = new Rule;
        $rule
            ->setAddress('app_servers:public')
            ->setPort('53')
            ->setProtocol('udp')
            ->setDirection('outbound')
            ->setAction('reject')
        ;

        $this
            ->firewall
            ->method('constructAddresses')
            ->willReturn(
                array(
                    '203.0.113.1',
                    '203.0.113.2',
                )
            )
        ;

        $g = new UfwGenerator($this->firewall);

        $ruleCmds = $g->generateRule($rule);

        $this->assertCount(2, $ruleCmds);

        $this->assertStringStartsWith(
            'ufw reject proto udp from any to 203.0.113.1 port 53 #',
            $ruleCmds[0]
        );

        $this->assertStringStartsWith(
            'ufw reject proto udp from any to 203.0.113.2 port 53 #',
            $ruleCmds[1]
        );
    }
}
