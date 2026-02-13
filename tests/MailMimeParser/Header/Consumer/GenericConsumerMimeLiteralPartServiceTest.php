<?php

namespace ZBateson\MailMimeParser\Header\Consumer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of GenericConsumerMimeLiteralPartServiceTest
 *
 * @author Zaahid Bateson
 */
#[CoversClass(GenericConsumerMimeLiteralPartService::class)]
#[CoversClass(AbstractConsumerService::class)]
#[CoversClass(AbstractGenericConsumerService::class)]
#[Group('Consumers')]
#[Group('GenericConsumerMimeLiteralPartService')]
class GenericConsumerMimeLiteralPartServiceTest extends TestCase
{
    // @phpstan-ignore-next-line
    private $genericConsumer;

    private $logger;

    protected function setUp() : void
    {
        $this->logger = \mmpGetTestLogger();
        $charsetConverter = $this->getMockBuilder(\ZBateson\MbWrapper\MbWrapper::class)
            ->onlyMethods([])
            ->getMock();
        $pf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\HeaderPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $mpf = $this->getMockBuilder(\ZBateson\MailMimeParser\Header\Part\MimeTokenPartFactory::class)
            ->setConstructorArgs([$this->logger, $charsetConverter])
            ->onlyMethods([])
            ->getMock();
        $qscs = $this->getMockBuilder(QuotedStringConsumerService::class)
            ->setConstructorArgs([$this->logger, $pf])
            ->onlyMethods([])
            ->getMock();
        $ccs = $this->getMockBuilder(CommentConsumerService::class)
            ->setConstructorArgs([$this->logger, $mpf, $qscs])
            ->onlyMethods([])
            ->getMock();
        $this->genericConsumer = new GenericConsumerMimeLiteralPartService($this->logger, $mpf, $ccs, $qscs);
    }

    public function testConsumeTokens() : void
    {
        $value = "Je\ \t suis\n ici";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Je  suis ici', $ret[0]->getValue());
    }

    public function testFilterSpacesBetweenMimeParts() : void
    {
        $value = "=?US-ASCII?Q?Je?=    =?US-ASCII?Q?suis?=\n=?US-ASCII?Q?ici?=";

        $ret = $this->genericConsumer->__invoke($value);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals('Jesuisici', $ret[0]);
    }

    protected function assertDecoded($expected, $encodedActual)
    {
        $ret = $this->genericConsumer->__invoke($encodedActual);
        $this->assertNotEmpty($ret);
        $this->assertCount(1, $ret);
        $this->assertEquals($expected, $ret[0]->getValue());
    }

    public function testDecodingTwoParts() : void
    {
        $kilgore = '=?US-ASCII?Q?Kilgore_Trout?=';
        $snow = '=?US-ASCII?Q?Jon_Snow?=';

        $this->assertDecoded(
            'Kilgore TroutJon Snow',
            " $kilgore   $snow "
        );
        $this->assertDecoded(
            'Kilgore TroutJon Snow',
            "{$kilgore}{$snow}"
        );
        $this->assertDecoded(
            'Kilgore Trout Jon',
            "$kilgore   Jon"
        );
        $this->assertDecoded(
            'Kilgore Jon Snow',
            "Kilgore   $snow"
        );
        $this->assertDecoded(
            'KilgoreJon SnowTrout',
            "Kilgore{$snow}Trout"
        );
        $this->assertDecoded('外為ｵﾝﾗｲﾝﾃﾞﾓ(25)(デモ)決済約定のお知らせ', '=?iso-2022-jp?Q?=1B$B300Y=1B(I5]W2]C^S=1B(B(25?=
            =?iso-2022-jp?Q?)(=1B$B%G%b=1B(B)=1B$B7h:QLsDj$N$*CN$i$;=1B(B?=');
    }
}
