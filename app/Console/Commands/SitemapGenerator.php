<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sitemap\Generator as CustomSitemapGenerator;

class SitemapGenerator extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sitemap:generate {--file_type=xml} {--file_path=}';

    /**
     * @var string
     */
    protected $description = 'Generates sitemap files in xml, csv, json formats.';

    const PAGES_DATA = [
        [
            'loc' => 'https://site.ru/',
            'lastmod' => '2020-12-14',
            'priority' => 1,
            'changefreq' => 'hourly'
        ],
        [
            'loc' => 'https://site.ru/news',
            'lastmod' => '2020-12-10',
            'priority' => 0.5,
            'changefreq' => 'daily'
        ],
        [
            'loc' => 'https://site.ru/about',
            'lastmod' => '2020-12-12',
            'priority' => 0.5,
            'changefreq' => 'daily'
        ],
        [
            'loc' => 'https://site.ru/products/ps5',
            'lastmod' => '2020-12-11',
            'priority' => 0.1,
            'changefreq' => 'weekly'
        ],
        [
            'loc' => 'https://site.ru/products/xbox',
            'lastmod' => '2020-12-12',
            'priority' => 0.1,
            'changefreq' => 'weekly'
        ],
        [
            'loc' => 'https://site.ru/products/wii',
            'lastmod' => '2020-12-11',
            'priority' => 0.1,
            'changefreq' => 'weekly'
        ]
    ];

    public function __construct(private readonly CustomSitemapGenerator $sitemapGenerator)
    {
        parent::__construct();
    }

    public function handle()
    {
        $sitemapFileType = $this->option('file_type');
        $sitemapFilePath = $this->option('file_path');
        if (! $sitemapFilePath) {
            $sitemapFilePath = storage_path('sitemaps');
        }

        $data = self::PAGES_DATA;
        $sitemapFilename = 'sitemap_'.date('Y-m-d').".{$sitemapFileType}";
        $this->prettyLog("Getting ready to generate the file {$sitemapFilename}");
        $this->prettyLog('Found '.count($data).' items. Start processing...');

        try {
            $sitemapFile = $this->sitemapGenerator->generateSitemap(self::PAGES_DATA, $sitemapFileType, $sitemapFilePath, $sitemapFilename);
            $this->prettyLog("File is ready by path: {$sitemapFile}");
            $this->prettyLog('Completed ✓ You are excellent: )', 'success');
        } catch (\Throwable $e) {
            $this->prettyLog('Oops something unexpected happened :( '. $e->getMessage(), 'error');
        }
    }

    private function prettyLog($msg, $type = 'default')
    {
        if ($type === 'error') {
            $style = 'fg=red';
        } elseif ($type === 'success') {
            $style = 'fg=green';
        } else {
            $style = 'fg=cyan';
        }

        $this->line($msg, $style);
    }
}
