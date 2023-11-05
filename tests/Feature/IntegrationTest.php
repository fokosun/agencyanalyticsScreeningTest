<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class IntegrationTest extends TestCase
{
    /**
     * @dataProvider DataProvider
     */
    public function test_the_application_returns_a_successful_response(
        string $website,
        string $expected,
        ?array $errors = null
    ): void {
        $view = $this->view(
            'welcome',
            [
                'results' => [],
                'links' => [],
                'website' => $website,
                'errors' => $errors
            ]
        );

        $view->assertSee('Crawl another website');
        $view->assertSeeText('HTTP status codes');
        $view->assertSee($website);

        if ($errors) {
            $view->assertSee(implode($errors));
        }
    }

    public static function DataProvider(): array
    {
        return [
            'Valid website' => [
                'website' => 'https://example.com',
                'expected' => 'example.com/'
            ],
            'Invalid website 1' => [
                'website' => 'Test .',
                'expected' => 'Test .',
                'errors' => ['The given website is Invalid. Try again.']
            ],
            'Invalid website 2' => [
                'website' => 'www.fakewebsite.com',
                'expected' => 'fakewebsite/',
                'errors' => ['The given website is Invalid. Try again.']
            ]
        ];
    }
}
