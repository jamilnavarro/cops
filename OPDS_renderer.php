<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

require_once ("base.php");
 
class OPDSRenderer
{
    const PAGE_OPENSEARCH = "8";
    const PAGE_OPENSEARCH_QUERY = "9";

    private $xmlStream = NULL;
    private $updated = NULL;
    
    private function getUpdatedTime () {
        if (is_null ($this->updated)) {
            $this->updated = time();
        }
        return date (DATE_ATOM, $this->updated);
    }
    
    private function getXmlStream () {
        if (is_null ($this->xmlStream)) {
            $this->xmlStream = new XMLWriter();
            $this->xmlStream->openMemory();
            $this->xmlStream->setIndent (true);
        }
        return $this->xmlStream;
    }
    
    public function getOpenSearch () {
        global $config;
        $xml = new XMLWriter ();
        $xml->openMemory ();
        $xml->setIndent (true);
        $xml->startDocument('1.0','UTF-8');
            $xml->startElement ("OpenSearchDescription");
                $xml->writeAttribute ("xmlns", "http://a9.com/-/spec/opensearch/1.1/");
                $xml->startElement ("ShortName");
                    $xml->text ("My catalog");
                $xml->endElement ();
                $xml->startElement ("InputEncoding");
                    $xml->text ("UTF-8");
                $xml->endElement ();
                $xml->startElement ("OutputEncoding");
                    $xml->text ("UTF-8");
                $xml->endElement ();
                $xml->startElement ("Image");
                    $xml->writeAttribute ("type", "image/x-icon");
                    $xml->writeAttribute ("width", "16");
                    $xml->writeAttribute ("height", "16");
                    $xml->text ("favicon.ico");
                $xml->endElement ();
                $xml->startElement ("Url");
                    $xml->writeAttribute ("type", 'application/atom+xml');
                    $xml->writeAttribute ("template", $config['cops_full_url'] . 'feed.php?query={searchTerms}');
                $xml->endElement ();
                $xml->startElement ("Query");
                    $xml->writeAttribute ("role", "example");
                    $xml->writeAttribute ("searchTerms", "robot");
                $xml->endElement ();
            $xml->endElement ();
        $xml->endDocument();
        return $xml->outputMemory(true);
    }
    
    private function startXmlDocument ($title) {
        self::getXmlStream ()->startDocument('1.0','UTF-8');
        self::getXmlStream ()->startElement ("feed");
            self::getXmlStream ()->writeAttribute ("xmlns", "http://www.w3.org/2005/Atom");
            self::getXmlStream ()->writeAttribute ("xmlns:xhtml", "http://www.w3.org/1999/xhtml");
            self::getXmlStream ()->writeAttribute ("xmlns:opds", "http://opds-spec.org/2010/catalog");
            self::getXmlStream ()->writeAttribute ("xmlns:opensearch", "http://a9.com/-/spec/opensearch/1.1/");
            self::getXmlStream ()->writeAttribute ("xmlns:dcterms", "http://purl.org/dc/terms/");
            self::getXmlStream ()->startElement ("title");
                self::getXmlStream ()->text ($title);
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("id");
                self::getXmlStream ()->text ($_SERVER['REQUEST_URI']);
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("updated");
                self::getXmlStream ()->text (self::getUpdatedTime ());
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("icon");
                self::getXmlStream ()->text ("favicon.ico");
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("author");
                self::getXmlStream ()->startElement ("name");
                    self::getXmlStream ()->text (utf8_encode ("Sébastien Lucas"));
                self::getXmlStream ()->endElement ();
                self::getXmlStream ()->startElement ("uri");
                    self::getXmlStream ()->text ("http://blog.slucas.fr");
                self::getXmlStream ()->endElement ();
                self::getXmlStream ()->startElement ("email");
                    self::getXmlStream ()->text ("sebastien@slucas.fr");
                self::getXmlStream ()->endElement ();
            self::getXmlStream ()->endElement ();
            $link = new LinkNavigation ("", "start", "Home");
            self::renderLink ($link);
            $link = new LinkNavigation ("?" . $_SERVER['QUERY_STRING'], "self");
            self::renderLink ($link);
            $link = new Link ("feed.php?page=" . self::PAGE_OPENSEARCH, "application/opensearchdescription+xml", "search", "Search here");
            self::renderLink ($link);
    }
        
    private function endXmlDocument () {
        self::getXmlStream ()->endElement ();
        self::getXmlStream ()->endDocument ();
        return self::getXmlStream ()->outputMemory(true);
    }
    
    private function renderLink ($link) {
        self::getXmlStream ()->startElement ("link");
            self::getXmlStream ()->writeAttribute ("href", $link->href);
            self::getXmlStream ()->writeAttribute ("type", $link->type);
            if (!is_null ($link->rel)) {
                self::getXmlStream ()->writeAttribute ("rel", $link->rel);
            }
            if (!is_null ($link->title)) {
                self::getXmlStream ()->writeAttribute ("title", $link->title);
            }
        self::getXmlStream ()->endElement ();
    }

    
    private function renderEntry ($entry) {
        self::getXmlStream ()->startElement ("title");
            self::getXmlStream ()->text ($entry->title);
        self::getXmlStream ()->endElement ();
        self::getXmlStream ()->startElement ("updated");
            self::getXmlStream ()->text (self::getUpdatedTime ());
        self::getXmlStream ()->endElement ();
        self::getXmlStream ()->startElement ("id");
            self::getXmlStream ()->text ($entry->id);
        self::getXmlStream ()->endElement ();
        self::getXmlStream ()->startElement ("content");
            self::getXmlStream ()->writeAttribute ("type", $entry->contentType);
            if ($entry->contentType == "text") {
                self::getXmlStream ()->text ($entry->content);
            } else {
                self::getXmlStream ()->writeRaw ($entry->content);
            }
        self::getXmlStream ()->endElement ();
        foreach ($entry->linkArray as $link) {
            self::renderLink ($link);
        }
        
        if (get_class ($entry) != "EntryBook") {
            return;
        }
        
        foreach ($entry->book->getAuthors () as $author) {
            self::getXmlStream ()->startElement ("author");
                self::getXmlStream ()->startElement ("name");
                    self::getXmlStream ()->text ($author->name);
                self::getXmlStream ()->endElement ();
                self::getXmlStream ()->startElement ("uri");
                    self::getXmlStream ()->text ($author->getUri ());
                self::getXmlStream ()->endElement ();
            self::getXmlStream ()->endElement ();
        }
        foreach ($entry->book->getTags () as $category) {
            self::getXmlStream ()->startElement ("category");
                self::getXmlStream ()->writeAttribute ("term", $category);
                self::getXmlStream ()->writeAttribute ("label", $category);
            self::getXmlStream ()->endElement ();
        }
        if (!is_null ($entry->book->pubdate)) {
            self::getXmlStream ()->startElement ("dcterms:issued");
                self::getXmlStream ()->text (date ("Y-m-d", $entry->book->pubdate));
            self::getXmlStream ()->endElement ();
        }

    }
    
    public function render ($page) {
        self::startXmlDocument ($page->title);
        if ($page->query)
        {
            self::getXmlStream ()->startElement ("opensearch:totalResults");
                self::getXmlStream ()->text (count($page->entryArray));
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("opensearch:itemsPerPage");
                self::getXmlStream ()->text (count($page->entryArray));
            self::getXmlStream ()->endElement ();
            self::getXmlStream ()->startElement ("opensearch:startIndex");
                self::getXmlStream ()->text ("1");
            self::getXmlStream ()->endElement ();
        }
        foreach ($page->entryArray as $entry) {
            self::getXmlStream ()->startElement ("entry");
                self::renderEntry ($entry);
            self::getXmlStream ()->endElement ();
        }
        return self::endXmlDocument ();
    }
}
 
?>
