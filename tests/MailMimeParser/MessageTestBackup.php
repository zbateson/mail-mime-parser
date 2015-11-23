<?php

use ZBateson\MailMimeParser\SimpleDi;

/**
 * Description of MessageTest
 *
 * @group Message
 * @author Zaahid Bateson
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    protected $di;
    
    public function setUp()
    {
        $this->di = SimpleDi::singleton();
    }
    
    public function testEmailHeader()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', 'user@example.com');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertEquals('user@example.com', $header->value);
    }
    
    public function testEmailHeaderWithEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '<user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertEquals('<user@example.com>', $header->value);
        $this->assertEquals('user@example.com', $header->addresses[0]->email);
    }
    
    public function testEmailHeaderWithNameEmailParts()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', 'Some User <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('Some User', $address->name);
    }
    
    public function testEmailHeaderWithUTF8UnicodeNameAndEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '=?UTF-8?B?zrrhvbnPg868zrUgZmzDuGRl?= <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('κόσμε fløde', $address->name);
    }
    
    public function testEmailHeaderWithUTF32UnicodeNameAndEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '=?UTF-32?Q?=FF=FE=00=00=BA=03=00=00y=1F=00=00=C3=03=00=00=BC=03=00=00?= ' .
            '=?UTF-32?Q?=FF=FE=00=00=B5=03=00=00=20=00=00=00f=00=00=00l=00=00=00?=' .
            '=?UTF-32?Q?=FF=FE=00=00=F8=00=00=00d=00=00=00e=00=00=00?= ' .
            '<user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('κόσμε fløde', $address->name);
        $this->assertEquals('κόσμε fløde <user@example.com>', $address->value);
        $this->assertEquals('κόσμε fløde <user@example.com>', $header->value);
    }
    
    public function testEmailHeaderWithWindows1256NameAndEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '=?WINDOWS-1256?B?5eHHIOXhxyDUzsjH0b8=?= <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('هلا هلا شخبار؟', $address->name);
    }
    
    public function testEmailHeaderWithISO2022JPNameAndEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '=?ISO-2022-JP?B?GyRCJCwkcyRQJGokXiQ5GyhC?= <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('がんばります', $address->name);
    }
    
    public function testEmailHeaderWithWindows1255NameAndEmailPart()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '=?WINDOWS-1255?B?6ePp8vog+fTkIODn+iDg6fDkIO7x9On35CA==?= <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('ידיעת שפה אחת אינה מספיקה', $address->name);
    }
    
    public function testEmailHeaderWithQuotedNameAndEmailParts()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '"Some User" <user@example.com>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('Some User', $address->name);
    }
    
    public function testEmailHeaderWithCommentPartsAndIgnorableSpaces()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('From', '"Some User" <user (test) @ example(blah(nested)).com(comme(n t"ing") )>');
        $header = $msg->getHeader('From');
        $this->assertNotNull($header);
        $this->assertCount(1, $header->addresses);
        $address = $header->addresses[0];
        $this->assertNotNull($address);
        $this->assertEquals('user@example.com', $address->email);
        $this->assertEquals('Some User', $address->name);
    }
    
    public function testEmailHeaderWithMultipleEmails()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('To', 'user@example.com, user2@example.com, user3@example.com');
        $header = $msg->getHeader('To');
        $this->assertNotNull($header);
        $addresses = $header->addresses;
        $this->assertNotNull($addresses);
        $this->assertCount(3, $addresses);
        $this->assertEquals('user@example.com', $addresses[0]->email);
        $this->assertEmpty($addresses[0]->name);
        $this->assertEquals('user2@example.com', $addresses[1]->email);
        $this->assertEmpty($addresses[1]->name);
        $this->assertEquals('user3@example.com', $addresses[2]->email);
        $this->assertEmpty($addresses[2]->name);
    }
    
    public function testEmailHeaderWithMultipleEmailsAndNames()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('To', 'Some User <user@example.com>, ' .
            '"Quoted Name" <user2@example.com>, noname@example.com, ' .
            '=?CP1256?B?5eHHIOXhxyDUzsjH0b8=?= <unicode@example.com>');
        $header = $msg->getHeader('to');
        $this->assertNotNull($header);
        $addresses = $header->addresses;
        $this->assertCount(4, $addresses);
        $this->assertEquals('user@example.com', $addresses[0]->email);
        $this->assertEquals('Some User', $addresses[0]->name);
        $this->assertEquals('user2@example.com', $addresses[1]->email);
        $this->assertEquals('Quoted Name', $addresses[1]->name);
        $this->assertEquals('noname@example.com', $addresses[2]->email);
        $this->assertEmpty($addresses[2]->name);
        $this->assertEquals('unicode@example.com', $addresses[3]->email);
        $this->assertEquals('هلا هلا شخبار؟', $addresses[3]->name);
    }
    
    public function testEmailHeaderWithMultipleEmailsNamesAndComments()
    {
        $msg = $this->di->newMessage();
        $msg->setRawHeader('To', 'Some (Silly) User <user   @ example . c(asdf)om>, ' .
            '"Quoted \"(Not a comment)\" Name" <user2@example.com>, noname@example.com, ' .
            '=?UTF-7?B?K0JrY0dSQVluICtCa2NHUkFZbiArQmpRR0xnWW9CaWNHTVFZZi0=?= <unicode@example(double (comment)).com> (ENDING Bad');
        $header = $msg->getHeader('To');
        $this->assertNotNull($header);
        $addresses = $header->addresses;
        $this->assertNotNull($addresses);
        $this->assertCount(4, $addresses);
        $this->assertEquals('user@example.com', $addresses[0]->email);
        $this->assertEquals('Some User', $addresses[0]->name);
        $this->assertEquals('user2@example.com', $addresses[1]->email);
        $this->assertEquals('Quoted "(Not a comment)" Name', $addresses[1]->name);
        $this->assertEquals('noname@example.com', $addresses[2]->email);
        $this->assertEmpty($addresses[2]->name);
        $this->assertEquals('unicode@example.com', $addresses[3]->email);
        $this->assertEquals('هلا هلا شخبار؟', $addresses[3]->name);
    }
}
