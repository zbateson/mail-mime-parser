<?php
namespace ZBateson\MailMimeParser\Header;

use PHPUnit_Framework_TestCase;
use ZBateson\MailMimeParser\Header\Consumer\ConsumerService;
use ZBateson\MailMimeParser\Header\Part\HeaderPartFactory;
use ZBateson\MailMimeParser\Header\Part\MimeLiteralPartFactory;

/**
 * Description of AddressHeaderTest
 *
 * @group Headers
 * @group AddressHeader
 * @covers ZBateson\MailMimeParser\Header\AddressHeader
 * @covers ZBateson\MailMimeParser\Header\AbstractHeader
 * @author Zaahid Bateson
 */
class AddressHeaderTest extends PHPUnit_Framework_TestCase
{
    protected $consumerService;
    
    protected function setUp()
    {
        $pf = new HeaderPartFactory();
        $mlpf = new MimeLiteralPartFactory();
        $this->consumerService = new ConsumerService($pf, $mlpf);
    }
    
    public function testEmptyHeader()
    {
        $header = new AddressHeader($this->consumerService, 'TO', '');
        $this->assertEquals('', $header->getValue());
        $this->assertNull($header->getPersonName());
    }
    
    public function testSingleAddress()
    {
        $header = new AddressHeader($this->consumerService, 'From', 'koolaid@dontdrinkit.com');
        $this->assertEquals('koolaid@dontdrinkit.com', $header->getValue());
        $this->assertEmpty($header->getPersonName());
        $this->assertEquals('From', $header->getName());
    }
    
    public function testAddressHeaderToString()
    {
        $header = new AddressHeader($this->consumerService, 'From', 'koolaid@dontdrinkit.com');
        $this->assertEquals('From: koolaid@dontdrinkit.com', $header);
    }
    
    public function testSingleAddressWithName()
    {
        $header = new AddressHeader($this->consumerService, 'From', 'Kool Aid <koolaid@dontdrinkit.com>');
        $this->assertEquals('koolaid@dontdrinkit.com', $header->getValue());
        $this->assertEquals('Kool Aid', $header->getPersonName());
        $addresses = $header->getParts();
        $this->assertCount(1, $addresses);
        $this->assertEquals('Kool Aid', $addresses[0]->getName());
        $this->assertEquals('koolaid@dontdrinkit.com', $addresses[0]->getValue());
    }
    
    public function testSingleAddressWithQuotedName()
    {
        $header = new AddressHeader($this->consumerService, 'To', '"Jürgen Schmürgen" <schmuergen@example.com>');
        $addresses = $header->getParts();
        $this->assertCount(1, $addresses);
        $this->assertEquals('Jürgen Schmürgen', $addresses[0]->getName());
        $this->assertEquals('schmuergen@example.com', $addresses[0]->getEmail());
    }
    
    public function testComplexSingleAddress()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'From',
            '=?US-ASCII?Q?Kilgore?= "Trout" <kilgore (writer) trout@"ilium" .ny. us>'
        );
        $addresses = $header->getParts();
        $this->assertCount(1, $addresses);
        $this->assertEquals('Kilgore Trout', $addresses[0]->getName());
        $this->assertEquals('kilgoretrout@ilium.ny.us', $addresses[0]->getEmail());
    }
    
    public function testMultipleAddresses()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'To',
            'thepilot@earth.com, The Little   Prince <theprince@ihatebaobabs.com> , '
            . '"The Fox"    <thefox@ilovetheprince.com>   ,    therose@pureawesome.com'
        );
        $addresses = $header->getParts();
        $this->assertCount(4, $addresses);
        $this->assertEquals('thepilot@earth.com', $addresses[0]->getEmail());
        $this->assertEquals('theprince@ihatebaobabs.com', $addresses[1]->getEmail());
        $this->assertEquals('The Little Prince', $addresses[1]->getName());
        $this->assertEquals('thefox@ilovetheprince.com', $addresses[2]->getEmail());
        $this->assertEquals('The Fox', $addresses[2]->getName());
        $this->assertEquals('therose@pureawesome.com', $addresses[3]->getEmail());
    }
    
    public function testAddressGroups()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'Cc',
            '=?US-ASCII?Q?House?= Stark: Arya Stark <arya(strong:personality)@winterfell.com>, robb@winterfell.com,'
            . 'Jon Snow <jsnow(that\'s right;)@nightswatch.com>; "House Lannister": tywin@lannister.com,'
            . '"Jaime Lannister" <jaime@lannister.com>, tyrion@lannister.com, Cersei Lannister <"cersei & cersei"@lannister.com>'
        );
        $parts = $header->getParts();
        $this->assertCount(2, $parts);
        
        $starks = $parts[0];
        $lannisters = $parts[1];
        $this->assertEquals('House Stark', $starks->getName());
        $this->assertEquals('House Lannister', $lannisters->getName());
        
        $this->assertCount(3, $starks->getAddresses());
        $this->assertCount(4, $lannisters->getAddresses());
    }
    
    public function testHasAddress()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'Cc',
            '=?US-ASCII?Q?House?= Stark: Arya Stark <arya(strong:personality)@winterfell.com>, robb@winterfell.com,'
            . 'Jon Snow <jsnow(that\'s right;)@nightswatch.com>; "House Lannister": tywin@lannister.com,'
            . '"Jaime Lannister" <jaime@lannister.com>, tyrion@lannister.com, Cersei Lannister <"cersei & cersei"@lannister.com>;'
            . 'maxpayne@addressunknown.com'
        );
        $this->assertTrue($header->hasAddress('arya@winterfell.com'));
        $this->assertTrue($header->hasAddress('jsnow@nightswatch.com'));
        // is this correct? Shouldn't it be cersei & cersei@lannister.com
        $this->assertTrue($header->hasAddress('cersei&cersei@lannister.com'));
        $this->assertTrue($header->hasAddress('maxpayne@addressunknown.com'));
        $this->assertFalse($header->hasAddress('nonexistent@example.com'));
    }
    
    public function testGetAddresses()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'Cc',
            '=?US-ASCII?Q?House?= Stark: Arya Stark <arya(strong:personality)@winterfell.com>, robb@winterfell.com,'
            . 'Jon Snow <jsnow(that\'s right;)@nightswatch.com>; "House Lannister": tywin@lannister.com,'
            . '"Jaime Lannister" <jaime@lannister.com>, tyrion@lannister.com, Cersei Lannister <"cersei & cersei"@lannister.com>;'
            . 'maxpayne@addressunknown.com'
        );
        $addresses = $header->getAddresses();
        $this->assertCount(8, $addresses);
        $parts = $header->getParts();
        
        foreach ($parts[0]->getAddresses() as $addr) {
            $this->assertSame($addr, current($addresses));
            next($addresses);
        }
        foreach ($parts[1]->getAddresses() as $addr) {
            $this->assertSame($addr, current($addresses));
            next($addresses);
        }
        $this->assertEquals('maxpayne@addressunknown.com', current($addresses)->getEmail());
    }
    
    public function testGetGroups()
    {
        $header = new AddressHeader(
            $this->consumerService,
            'Cc',
            '=?US-ASCII?Q?House?= Stark: Arya Stark <arya(strong:personality)@winterfell.com>, robb@winterfell.com,'
            . 'Jon Snow <jsnow(that\'s right;)@nightswatch.com>; "House Lannister": tywin@lannister.com,'
            . '"Jaime Lannister" <jaime@lannister.com>, tyrion@lannister.com, Cersei Lannister <"cersei & cersei"@lannister.com>;'
            . 'maxpayne@addressunknown.com'
        );
        $groups = $header->getGroups();
        $parts = $header->getParts();
        $this->assertCount(2, $groups);
        $this->assertSame($parts[0], $groups[0]);
        $this->assertSame($parts[1], $groups[1]);
    }
}
