<?php

namespace Tests\Model;

use Model\CardName;
use PHPUnit\Framework\TestCase;

class CardNameTest extends TestCase
{
    /**
     * @test
     * @dataProvider isValid_data
     */
    public function test_isValid($name, $expectedIsValid)
    {
        $result = CardName::isValid($name);
        $this->assertEquals($expectedIsValid, $result);
    }

    public function isValid_data(): array
    {
        return [
            ['PIC-0001', true],
            ['PIC--0001', false],
            ['PI0001', false],
            ['PIC001', false],
            ['APIC0001', false],
            ['APIC-0001', false],
        ];
    }

    public function test_getSample_should_return_a_valid_name()
    {
        $this->assertTrue(CardName::isValid(CardName::getSample()), 'The card name sample is invalid. Did you change something and forgot to update it?');
    }
}