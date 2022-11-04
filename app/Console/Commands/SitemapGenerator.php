<?php

namespace App\Console\Commands;

use App\Console\Commands\Exceptions\FileCreatingException;
use App\Console\Commands\Exceptions\UnexceptedFileTypeException;
use App\Console\Commands\Exceptions\UnexceptedResultException;
use Illuminate\Console\Command;
use SimpleXMLElement;

class SitemapGenerator extends Command
{
    protected $signature = 'sitemap:generate {--file_type=xml} {--file_path=}';

    protected $description = 'Generates sitemap files in xml, csv, json formats.';

    const SITEMAP_FILE_TYPE__XML = 'xml';
    const SITEMAP_FILE_TYPE__CSV = 'csv';
    const SITEMAP_FILE_TYPE__JSON = 'json';

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

    /**
     * @throws UnexceptedFileTypeException
     * @throws UnexceptedResultException
     * @throws FileCreatingException
     */
    public function handle()
    {
        $sitemapFileType = $this->option('file_type');
        $sitemapFilePath = $this->option('file_path');
        if (! $sitemapFilePath) {
            $sitemapFilePath = storage_path('sitemaps');
        }

        $data = self::PAGES_DATA;
        $sitemapFilename = 'sitemap_'.date('Y-m-d').".{$sitemapFileType}";
        $this->prettylog("Getting ready to generate the file {$sitemapFilename}");
        $this->prettylog('Found '.count($data).' items. Start processing...');

        if ($sitemapFileType === self::SITEMAP_FILE_TYPE__XML) {
            $content = $this->generateXmlFromArray(self::PAGES_DATA);
        } elseif ($sitemapFileType === self::SITEMAP_FILE_TYPE__JSON) {
            $content = $this->generateJsonFromArray(self::PAGES_DATA);
        } elseif ($sitemapFileType === self::SITEMAP_FILE_TYPE__CSV) {
            $content = $this->generateCsvFromArray(self::PAGES_DATA, ';');
        } else {
            $this->prettylog('Oops something unexpected happened :(', 'error');
            throw new UnexceptedFileTypeException();
        }

        if (! file_exists($sitemapFilePath)) {
            mkdir($sitemapFilePath, 0777, true);
        }

        $sitemapFile = "{$sitemapFilePath}/sitemap.{$sitemapFileType}";
        $result = file_put_contents($sitemapFile, $content);
        if (! $result) {
            throw new FileCreatingException('Unable to create sitemap file', 100503);
        }
        $this->prettylog("File is ready by path: {$sitemapFile}");
        $this->prettylog('Completed ✓ You are excellent: )', 'success');
    }

    private function prettylog($msg, $type = 'default')
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

    /**
     * @param array $data
     * @return bool|string
     * @throws UnexceptedResultException
     */
    private function generateXmlFromArray(array $data): bool|string
    {
        $xml = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><urlset xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd' />");
        $this->multidimensionalArrayToXml($xml, $data, 'url');

        $xmlStr = $xml->asXML();
        if (! $xmlStr) {
            throw new UnexceptedResultException('Unexpected error occurred. Check your file type and try again.', 100501);
        }

        return $xmlStr;
    }

    /**
     * @param array $data
     * @param string $delimiter
     * @return string
     */
    private function generateCsvFromArray(array $data, string $delimiter=':'): string
    {
        $csvStr = '';
        $titles = array_keys($data[0] ?? []);
        $csvStr .= implode($delimiter, $titles).PHP_EOL;
        foreach ($data as $item) {
            $csvStr .= implode($delimiter, $item).PHP_EOL;
        }

        return $csvStr;
    }

    /**
     * @throws UnexceptedResultException
     */
    private function generateJsonFromArray(array $data): string
    {
        $jsonStr = json_encode($data, JSON_PRETTY_PRINT);
        if (! $jsonStr) {
            throw new UnexceptedResultException('Unexpected error occurred. Check your file type and try again.', 100502);
        }

        return $jsonStr;
    }

    /**
     * @param \SimpleXMLElement $xmlObj
     * @param array $data
     * @param string $nodeKey
     * @return void
     */
    private function multidimensionalArrayToXml(SimpleXMLElement $xmlObj, array $data, string $nodeKey = 'item'): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $nodeKey;
            }
            if (is_array($value)) {
                $node = $xmlObj->addChild($key);
                $this->multidimensionalArrayToXml($node, $value);
            } else {
                $xmlObj->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
