<?php
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\DomCrawler;

class Browser
{
    private $url;

    function __construct($url)
    {
        $this->url = $url;
    }

    function getContents()
    {
        return file_get_contents($this->url);
    }

    function getLastupSelector()
    {
        return '.update > li > b';
    }
}

class Crawler
{
    /** @var DomCrawler\Crawler */
    private $crawler;

    private $lastup;

    private $browser;

    function __construct(Browser $browser)
    {
        $this->browser = $browser;
        $this->setCrawler();
    }

    function setCrawler($crawler = null)
    {
        if ($crawler === null) {
            $crawler = new DomCrawler\Crawler($this->browser->getContents());
        }
        $this->crawler = $crawler;
    }

    function getLastup()
    {
        if ($this->lastup === null) {
            $this->lastup = $this->crawler->filter($this->browser->getLastupSelector())->text();
        }
        return $this->lastup;
    }
}

class Sender
{
    private $valid;
    private $message;
    private $subject;

    function __construct($message, $subject = 'ekidata-feed')
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->valid = null;
    }

    function send()
    {
        $this->valid = mail('to-mail-address@example.com', $this->subject, $this->message, 'From: from-mail-address@example.com');
        echo date('Y/m/d H:i:s')." send({$this->valid})".PHP_EOL;
        return $this->valid;
    }

    function isValid()
    {
        return $this->valid;
    }
}

class Client
{
    const FILENAME = 'lastup.txt';
    const MESSAGE = 'ekidata-update';

    /**
     * @var Crawler
     */
    private $crawler;

    private $newLastup;
    private $oldLastup;

    function __construct($url)
    {
        echo date('Y/m/d H:i:s') . " start".PHP_EOL;
        $this->crawler = new \Crawler(new Browser($url));
        $this->newLastup = $this->crawler->getLastup();
        $this->oldLastup = $this->readLastup();

        if ($this->isUpdate()) {
            $sender = new Sender(self::MESSAGE, self::MESSAGE . " " . $this->newLastup);
            $sender->send();
        }
        $this->writeLastup();
        echo date('Y/m/d H:i:s') . " end".PHP_EOL;
    }

    function isUpdate()
    {
        if ($this->oldLastup < $this->newLastup) {
            return true;
        }
        return false;
    }

    function readLastup()
    {
        return @file_get_contents(self::FILENAME);
    }
    function writeLastup()
    {
        return file_put_contents(self::FILENAME, $this->newLastup);
    }
}

try {
    $client = new Client('https://www.ekidata.jp/');
} catch (\Exception $ex){
    echo date('Y/m/d H:i:s') . " " . $ex->getTraceAsString() . " " . $ex->getMessage();
}

