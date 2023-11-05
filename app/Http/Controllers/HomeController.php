<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\WebCrawlerUtil;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request, WebCrawlerUtil $crawler)
    {
        $errors = [];

        if ($request->getMethod() == "POST") {
            $url = $request->url;

            if ($this->isValid($url)) {
                $crawler->setUrl($url);
                $crawler->setLinksDepth($request->level);
            } else {
                $errors = 'The given website is Invalid. Try again.';
            }
        }

        $results = $crawler->crawl()->getResults();

        return view(
            'welcome',
            [
                'results' => $results[0],
                'links' => $results[1],
                'website' => $crawler->getEntryPointUrl(),
                'errors' => $errors
            ]
        );
    }

    private function isValid(string $website): bool
    {
        $parts = parse_url($website);

        if (!isset($parts['scheme']) && !isset($parts['host'])) {
            return false;
        }

        return true;
    }
}
