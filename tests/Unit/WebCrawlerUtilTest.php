<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\WebCrawlerUtil;
use PHPUnit\Framework\TestCase;

class WebCrawlerUtilTest extends TestCase
{
    public function test_it_can_crawl_a_website_successfully(): void
    {
        $expected = [
            'Pages Crawled',
            'Unique Images',
            'Unique Internal Links',
            'Unique External Links',
            'Average page load in seconds',
            'Average Word Count',
            'Average Title Length',
        ];

        $webCrawlerUtil = resolve(WebCrawlerUtil::class, ['url' => "https://agencyanalytics.com/"]);

        $results = $webCrawlerUtil->crawl()->getResults();

        $this->assertSame(2, count($results));
        $this->assertSame($expected, array_keys($results[0]));
        $this->assertSame(6, count($results[1]));
        $this->assertSame("agencyanalytics.com/", $webCrawlerUtil->getEntryPointUrl());
    }

    /**
     * @dataProvider PagesProvider
     */
    public function test_it_returns_crawled_pages_with_their_http_status_code(string $url, int $statusCode): void
    {
        $webCrawlerUtil = resolve(WebCrawlerUtil::class, ['url' => $url]);

        $results = $webCrawlerUtil->crawl()->getResults();

        $this->assertArrayHasKey($url, $results[1]);
        $this->assertSame($results[1][$url], $statusCode);
    }

    public static function PagesProvider(): array
    {
        return [
            'Unexpected 403 page' => [
                'url' => 'https://agencyanalytics.com/403',
                'status' => 404
            ],
            'Unexpected about page' => [
                'url' => 'https://agencyanalytics.com/about',
                'status' => 404
            ],
            'Unexpected errors page' => [
                'url' => 'https://agencyanalytics.com/errors',
                'status' => 404
            ],
            'Unexpected 500 page' => [
                'url' => 'https://agencyanalytics.com/500',
                'status' => 500
            ]
        ];
    }

    public function test_it_can_crawl_any_website_given_the_url_and_the_depth_to_crawl()
    {
        $expected = [
            'Pages Crawled',
            'Unique Images',
            'Unique Internal Links',
            'Unique External Links',
            'Average page load in seconds',
            'Average Word Count',
            'Average Title Length',
        ];

        $webCrawlerUtil = resolve(WebCrawlerUtil::class, ['url' => '']);

        $webCrawlerUtil->setUrl("https://example.com/");
        $webCrawlerUtil->setLinksDepth(5);

        $results = $webCrawlerUtil->crawl()->getResults();

        $this->assertSame(2, count($results));
        $this->assertSame($expected, array_keys($results[0]));

        $this->assertSame("example.com/", $webCrawlerUtil->getEntryPointUrl());
    }
}
