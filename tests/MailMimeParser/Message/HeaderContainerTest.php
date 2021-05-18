<?php
namespace ZBateson\MailMimeParser\Message;

use LegacyPHPUnit\TestCase;

/**
 * Description of HeaderContainerTest
 *
 * @group Message
 * @group HeaderContainer
 * @covers ZBateson\MailMimeParser\Header\HeaderContainer
 * @author Zaahid Bateson
 */
class HeaderContainerTest extends TestCase
{
    protected $mockHeaderFactory;

    protected function legacySetUp()
    {
        $this->mockHeaderFactory = $this->getMockBuilder('ZBateson\MailMimeParser\Header\HeaderFactory')
            ->disableOriginalConstructor()
            ->setMethods(['newInstance'])
            ->getMock();
    }

    public function testAddExistsGet()
    {
        $ob = new HeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('second', 'value');

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertFalse($ob->exists('third'));
        $this->assertFalse($ob->exists('first', 1));

        $this->mockHeaderFactory
            ->expects($this->exactly(2))
            ->method('newInstance')
            ->withConsecutive(
                [ 'first', 'value' ],
                [ 'second', 'value' ]
            )
            ->willReturnOnConsecutiveCalls('first-value', 'second-value');

        $this->assertEquals('first-value', $ob->get('first'));
        $this->assertEquals('second-value', $ob->get('second'));
        $this->assertEquals('first-value', $ob->get('first', 0));
        $this->assertEquals('first-value', $ob->get('first'));
        $this->assertEquals('second-value', $ob->get('second', 0));
        $this->assertEquals('second-value', $ob->get('second'));

        $this->assertNull($ob->get('other'));
        $this->assertNull($ob->get('second', 1));

        $headers = [ [ 'first', 'value' ], [ 'second', 'value' ] ];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddExistsGetSameName()
    {
        $ob = new HeaderContainer($this->mockHeaderFactory);
        $ob->add('repeated', 'first');
        $ob->add('repeated', 'second');
        $ob->add('repeated', 'third');

        $this->assertTrue($ob->exists('repeated'));
        $this->assertTrue($ob->exists('repeated', 0));
        $this->assertTrue($ob->exists('repeated', 1));
        $this->assertTrue($ob->exists('repeated', 2));
        $this->assertFalse($ob->exists('repeated', 3));
        $this->assertFalse($ob->exists('something-else'));

        $this->mockHeaderFactory
            ->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                [ 'repeated', 'first' ],
                [ 'repeated', 'second' ],
                [ 'repeated', 'third' ]
            )
            ->willReturnOnConsecutiveCalls('repeated-first', 'repeated-second', 'repeated-third');

        $this->assertEquals('repeated-first', $ob->get('repeated'));
        $this->assertEquals('repeated-first', $ob->get('repeated', 0));
        $this->assertEquals('repeated-second', $ob->get('repeated', 1));
        $this->assertEquals('repeated-third', $ob->get('repeated', 2));

        $instanceHeaders = [
            'repeated-first', 'repeated-second', 'repeated-third'
        ];
        $this->assertEquals($instanceHeaders, $ob->getAll('repeated'));

        $this->assertNull($ob->get('other'));
        $this->assertNull($ob->get('repeated', 3));

        $headers = [
            [ 'repeated', 'first' ],
            [ 'repeated', 'second' ],
            [ 'repeated', 'third' ]
        ];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddSetExistsGet()
    {
        $ob = new HeaderContainer($this->mockHeaderFactory);
        $ob->set('first', 'value');
        $ob->set('second', 'value');
        $ob->set('third', 'value');

        $ob->add('first', 'second-first');
        $ob->add('second', 'second-second');

        $ob->set('first', 'updated-value');
        $ob->set('second', 'second-updated-value', 1);

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('first', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertTrue($ob->exists('third'));

        $this->mockHeaderFactory
            ->expects($this->exactly(5))
            ->method('newInstance')
            ->withConsecutive(
                [ 'first', 'updated-value' ],
                [ 'second', 'second-updated-value' ],
                [ 'first', 'second-first' ],
                [ 'second', 'value' ],
                [ 'third', 'value' ]
            )
            ->willReturnOnConsecutiveCalls(
                'first-updated-value',
                'second-second-updated-value',
                'second-first-value',
                'second-value',
                'third-value'
            );

        $this->assertEquals('first-updated-value', $ob->get('first'));
        $this->assertEquals('second-second-updated-value', $ob->get('second', 1));
        $this->assertEquals('second-first-value', $ob->get('first', 1));
        $this->assertEquals('second-value', $ob->get('second'));
        $this->assertEquals('third-value', $ob->get('third'));

        $instanceHeaders = [
            'first-updated-value', 'second-first-value'
        ];
        $this->assertEquals($instanceHeaders, $ob->getAll('first'));

        $headers = [
            [ 'first', 'updated-value' ],
            [ 'second', 'value' ],
            [ 'third', 'value' ],
            [ 'first', 'second-first' ],
            [ 'second', 'second-updated-value' ]
        ];
        $this->assertEquals($headers, $ob->getHeaders());
    }

    public function testAddRemoveGet()
    {
        $ob = new HeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('second', 'value');
        $ob->add('third', 'value');

        $this->assertTrue($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('third'));

        $ob->remove('first');

        $this->assertFalse($ob->exists('first'));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('third'));

        $this->mockHeaderFactory
            ->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                [ 'second', 'value' ],
                [ 'third', 'value' ],
                [ 'second', 'updated' ]
            )
            ->willReturnOnConsecutiveCalls('second-value', 'third-value', 'second-updated');

        $this->assertNull($ob->get('first'));
        $this->assertEquals('second-value', $ob->get('second'));
        $this->assertEquals('third-value', $ob->get('third'));
        $headers = [
            [ 'second', 'value' ],
            [ 'third', 'value' ],
        ];
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->remove('second');
        $headers = [
            [ 'third', 'value' ]
        ];
        $this->assertNull($ob->get('second'));
        $this->assertEquals('third-value', $ob->get('third'));
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->set('second', 'updated');
        $headers = [
            [ 'third', 'value' ],
            [ 'second', 'updated' ]
        ];
        $this->assertEquals($headers, $ob->getHeaders());
        $this->assertEquals('second-updated', $ob->get('second'));
    }

    public function testAddRemoveAllGet()
    {
        $ob = new HeaderContainer($this->mockHeaderFactory);
        $ob->add('first', 'value');
        $ob->add('first', 'second-first');
        $ob->add('second', 'value');
        $ob->add('second', 'second-second');
        $ob->add('second', 'third-second');
        $ob->add('third', 'value');

        $this->assertTrue($ob->exists('FIRST'));
        $this->assertTrue($ob->exists('first', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('SECOND', 1));
        $this->assertTrue($ob->exists('second', 2));
        $this->assertTrue($ob->exists('third'));

        $ob->remove('FIRST');

        $this->assertTrue($ob->exists('FiRST'));
        $this->assertFalse($ob->exists('fIRst', 1));
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertTrue($ob->exists('second', 2));
        $this->assertTrue($ob->exists('third'));

        $this->mockHeaderFactory
            ->expects($this->exactly(3))
            ->method('newInstance')
            ->withConsecutive(
                [ 'first', 'second-first' ],
                [ 'second', 'value' ],
                [ 'second', 'third-second' ]
            )
            ->willReturnOnConsecutiveCalls('second-first-value', 'second-value', 'second-third-second-value');

        $this->assertNull($ob->get('first', 1));
        $this->assertEquals('second-first-value', $ob->get('first'));

        $ob->remove('second', 1);
        $this->assertTrue($ob->exists('second'));
        $this->assertTrue($ob->exists('second', 1));
        $this->assertFalse($ob->exists('second', 2));

        $this->assertEquals('second-value', $ob->get('second'));
        $this->assertEquals('second-third-second-value', $ob->get('second', 1));
        $this->assertNull($ob->get('second', 2));

        $ob->removeAll('second');
        $this->assertFalse($ob->exists('second'));

        $headers = [
            [ 'first', 'second-first' ],
            [ 'third', 'value' ],
        ];
        $this->assertEquals($headers, $ob->getHeaders());

        $ob->set('second', 'new-value', 3);
        $headers[] = [ 'second', 'new-value' ];
        $this->assertEquals($headers, $ob->getHeaders());
    }
}
