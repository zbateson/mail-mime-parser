<?php
use ZBateson\MailMimeParser\SimpleDi as SimpleDi;

/**
 * Description of AddressHeaderTest
 *
 * @group Headers
 * @group AddressHeader
 * @author Zaahid Bateson
 */
class AddressHeaderTest extends \PHPUnit_Framework_TestCase
{
    protected $headerFactory;
    
    public function setup()
    {
        $di = SimpleDi::singleton();
        $this->headerFactory = $di->getHeaderFactory();
    }
    
    public function testInstance()
    {
        $aValid = ['BCC', 'to', 'FrOM'];
        $aNot = ['MESSAGE-ID', 'date', 'Subject'];
        foreach ($aValid as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
        foreach ($aNot as $name) {
            $header = $this->headerFactory->newInstance($name, 'Test');
            $this->assertNotNull($header);
            $this->assertNotEquals('ZBateson\MailMimeParser\Header\AddressHeader', get_class($header));
        }
    }
    
    public function testSingleAddress()
    {
        $header = $this->headerFactory->newInstance('From', 'koolaid@dontdrinkit.com');
        $this->assertNotNull($header);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('koolaid@dontdrinkit.com', $address->email);
        $this->assertNull($address->name);
    }
    
    public function testSingleAddressWithName()
    {
        $header = $this->headerFactory->newInstance('From', 'Kool Aid <koolaid@dontdrinkit.com>');
        $this->assertNotNull($header);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('Kool Aid', $address->name);
        $this->assertEquals('koolaid@dontdrinkit.com', $address->email);
    }
    
    public function testSingleAddressWithQuotedName()
    {
        $header = $this->headerFactory->newInstance('To', '"Jürgen Schmürgen" <schmuergen@example.com>');
        $this->assertNotNull($header);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('Jürgen Schmürgen', $address->name);
        $this->assertEquals('schmuergen@example.com', $address->email);
    }
    
    public function testComplexSingleAddress()
    {
        $header = $this->headerFactory->newInstance(
            'From',
            '=?US-ASCII?Q?Kilgore?= "Trout" <kilgore (writer) trout@"ilium" .ny. us>'
        );
        $this->assertNotNull($header);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('Kilgore Trout', $address->name);
        $this->assertEquals('kilgoretrout@ilium.ny.us', $address->email);
    }
    
    public function testMultipleAddresses()
    {
        $header = $this->headerFactory->newInstance(
            'To',
            'thepilot@earth.com, The Little   Prince <theprince@ihatebaobabs.com> , '
            . '"The Fox"    <thefox@ilovetheprince.com>   ,    therose@pureawesome.com'
        );
        $this->assertNotNull($header);
        $addresses = $header->addresses;
        $this->assertCount(4, $addresses);
        $this->assertEquals('thepilot@earth.com', $addresses[0]->email);
        $this->assertEquals('theprince@ihatebaobabs.com', $addresses[1]->email);
        $this->assertEquals('The Little Prince', $addresses[1]->name);
        $this->assertEquals('thefox@ilovetheprince.com', $addresses[2]->email);
        $this->assertEquals('The Fox', $addresses[2]->name);
        $this->assertEquals('therose@pureawesome.com', $addresses[3]->email);
    }
    
    public function testAddressGroups()
    {
        $header = $this->headerFactory->newInstance(
            'Cc',
            '=?US-ASCII?Q?House?= Stark: Arya Stark <arya(strong:personality)@winterfell.com>, robb@winterfell.com,'
            . 'Jon Snow <jsnow(that\'s right;)@nightswatch.com>; "House Lannister": tywin@lannister.com,'
            . '"Jaime Lannister" <jaime@lannister.com>, tyrion@lannister.com, Cersei Lannister <"cersei & cersei"@lannister.com>'
        );
        $this->assertNotNull($header);
        $addresses = $header->addresses;
        $groups = $header->groups;
        $this->assertCount(7, $addresses);
        $this->assertCount(2, $groups);
        
        $starks = $header->groups[0];
        $lannisters = $header->groups[1];
        $this->assertEquals('House Stark', $starks->name);
        $this->assertEquals('House Lannister', $lannisters->name);
        
        $this->assertEquals('Arya Stark', $addresses[0]->name);
        $this->assertEquals('arya@winterfell.com', $addresses[0]->email);
        $this->assertNull($addresses[1]->name);
        $this->assertEquals('robb@winterfell.com', $addresses[1]->email);
        $this->assertEquals('Jon Snow', $addresses[2]->name);
        $this->assertEquals('jsnow@nightswatch.com', $addresses[2]->email);
        for ($i = 0; $i < 3; ++$i) {
            $this->assertSame($addresses[$i], $starks->addresses[$i]);
        }
        
        $this->assertNull($addresses[3]->name);
        $this->assertEquals('tywin@lannister.com', $addresses[3]->email);
        $this->assertEquals('Jaime Lannister', $addresses[4]->name);
        $this->assertEquals('jaime@lannister.com', $addresses[4]->email);
        $this->assertNull($addresses[5]->name);
        $this->assertEquals('tyrion@lannister.com', $addresses[5]->email);
        $this->assertEquals('Cersei Lannister', $addresses[6]->name);
        $this->assertEquals('cersei & cersei@lannister.com', $addresses[6]->email);
        for ($i = 0, $j = 3; $i < 4; ++$i, ++$j) {
            $this->assertSame($addresses[$j], $lannisters->addresses[$i]);
        }
    }
}
