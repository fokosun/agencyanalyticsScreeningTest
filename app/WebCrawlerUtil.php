<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\TransferStats;
use DOMDocument;
use Illuminate\Support\Str;

class WebCrawlerUtil
{
    protected string $url;
    protected int $statusCode;
    protected int $linksDepth = 6;
    protected int $pagesCrawled = 0;
    protected float $uniqueImages = 0;
    protected int $uniqueInternalLinks = 0;
    protected int $uniqueExternalLinks = 0;
    protected float $averagePageLoadTime = 0;
    protected float $averageWordCount = 0;
    protected float $averageTitleLength = 0;
    protected string $scheme = '';
    protected string $host = '';
    protected array $linksToCrawl = [];
    protected float $pageLoadTime = 0;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function setLinksDepth(int $depth)
    {
        $this->linksDepth = $depth;
    }

    public function getLinksCrawled(): array
    {
        return $this->linksToCrawl;
    }

    public function getEntryPointUrl(): string
    {
        $parts = parse_url($this->url);
        $host = $parts['host'] ?? 'https://';
        $path = $parts['path'] ?? '/';

        return $host . $path;
    }

    public function getResults(): array
    {
        return [
           [
               'Pages Crawled' => $this->pagesCrawled,
               'Unique Images' => $this->uniqueImages,
               'Unique Internal Links' => $this->uniqueInternalLinks,
               'Unique External Links' => $this->uniqueExternalLinks,
               'Average page load in seconds' => $this->averagePageLoadTime,
               'Average Word Count' => $this->averageWordCount,
               'Average Title Length' => $this->averageTitleLength,
           ],
            $this->getLinksCrawled()
        ];
    }

    public function crawl(): self
    {
        $depth = $this->linksDepth - 1;
        $startingPage = parse_url($this->url)['path'] ?? '/';

        //Get starting page data
        $html = $this->getHtml($this->url);

        $totalImgCount = 0;
        $totalWordCount = 0;
        $pageTitleLength = 0;
        $externalLinks = [];
        $base = $this->scheme . '://' . $this->host;

        $domDoc = new DOMDocument();
        $domDoc->loadHTML($html, LIBXML_NOERROR);

        //How many pages are there?
        $linksOnStartingPage = $domDoc->getElementsByTagName('a');

        if ($linksOnStartingPage->length > 0) {
            foreach($linksOnStartingPage as $link) {
                $linkHref = $link->getAttribute('href');

                //exclude the starting page, etc
                if ($linkHref == $startingPage) {
                    continue;
                }

                //exclude hyperlink and other
                if (Str::startsWith($linkHref, ['#', ''])) {
                    continue;
                }

                if (
                    Str::startsWith($linkHref, 'https://') &&
                    !Str::contains($linkHref, $this->host)
                ) {
                    $externalLinks[] = $linkHref;
                }

                if (count($this->linksToCrawl) <= $depth) {
                    //TODO: Address all possible tlds e.g .co .ca .co.uk etc
                    if (!Str::contains($linkHref, $base)) {
                        $this->linksToCrawl[rtrim($base . $linkHref, '/')] =  null;
                    } else {
                        $this->linksToCrawl[rtrim($linkHref, '/')] =  null;
                    }
                }
            }
        }

        //based on requirements, 4-6 pages
        if (count($this->linksToCrawl) >= 4) {
            $pagesCrawled = count($this->linksToCrawl);

            //Crawl and increment metrics
            foreach ($this->linksToCrawl as $url => $status) {
                $html = $this->getHtml($url);

                $totalImgCount+= $this->getImageCount($html);
                $totalWordCount += $this->getWordCount($html);
                $pageTitleLength += $this->getPageTitleLength($html);
            }

            $this->pagesCrawled = $pagesCrawled;
            $this->uniqueImages = round($totalImgCount / $pagesCrawled);
            $this->uniqueInternalLinks = count(array_unique($this->linksToCrawl));
            $this->uniqueExternalLinks = count(array_unique($externalLinks));
            $this->averageWordCount = round($totalWordCount / $pagesCrawled);
            $this->averagePageLoadTime = round(($this->pageLoadTime / $pagesCrawled), 8);
            $this->averageTitleLength = round($pageTitleLength / $pagesCrawled);
        }

        return $this;
    }

    /**
     * @throws GuzzleException
     */
    private function getHtml(string $url): string
    {
        $client =  new Client();

        $response = $client->get($url, [
            'on_stats' => function (TransferStats $stats) {
                $this->pageLoadTime += $stats->getTransferTime() / 1000;
            },
            'http_errors' => false
        ]);

        $this->linksToCrawl[rtrim($url, '/')] = $response->getStatusCode();
        $parse_url = parse_url($url);
        $this->scheme = $parse_url['scheme'];
        $this->host = $parse_url['host'];

        return $response->getBody()->getContents();
    }

    /**
     * This includes words found in the head and body section of the DOM
     */
    private function getWordCount(string $html): int
    {
        //remove the js, head, styles and comments
        $search = array('@<script[^>]*?>.*?</script>@si',
            '@<head>.*?</head>@siU',
            '@<style[^>]*?>.*?</style>@siU',
            '@<![\s\S]*?--[ \t\n\r]*>@'
        );

        $contents = preg_replace($search, '', $html);

        //strip any remaining html tags
        $contents = strip_tags($contents);

        return count(array_count_values(str_word_count($contents, 1)));
    }

    private function getImageCount(string $html): int
    {
        $images = $this->getElementsByTagName($html, 'img');
        $imgLinks = [];

        foreach ($images as $image) {
            $imgLinks[] = $image->getAttribute('src');
        }

        return count(array_unique($imgLinks));
    }

    private function getPageTitleLength(string $html): int
    {
        return strlen($this->getElementsByTagName($html, 'title')[0]->textContent ?: "");
    }

    private function getElementsByTagName(string $html, string $tagName)
    {
        $domDoc = new DOMDocument();
        $domDoc->loadHTML($html, LIBXML_NOERROR);

        return $domDoc->getElementsByTagName($tagName);
    }
}
