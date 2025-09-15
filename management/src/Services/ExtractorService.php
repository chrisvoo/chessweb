<?php

namespace Scacchilatorre\Management\Services;

use DateTime;
use DOMDocument;
use DOMXPath;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractorService implements Service
{
    private bool $dryRun = false;
    private SymfonyStyle $io;

    public function withIO(SymfonyStyle $io): Service
    {
        $this->io = $io;
        return $this;
    }

    public function getFileListIterator(string $dumpPath): array
    {
        $dirIterator = new RecursiveDirectoryIterator($dumpPath);
        $path = $dirIterator->getPath();
        $dirIterator->setFlags(FilesystemIterator::CURRENT_AS_SELF);
        $this->io->writeln('- Scanning ' . $path . '...');

        $fileIterator = new RecursiveIteratorIterator($dirIterator);
        return [
            'file_list_iterator' => new RegexIterator($fileIterator, '/^(\d{4}|index)\.php$/'),
            'path' => $path
        ];
    }

    public function getFileContent($filename): string
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            $this->io->error('Error: cannot read file ' . $filename);
            exit(1);
        }

        $result = preg_replace('/<\?php.*?\?>/s', '', $content);
        return str_replace('\\', "/", $result);
    }

    public function extractPostsFromHtml($htmlContent): array
    {
        libxml_use_internal_errors(true); // Suppress DOM warnings for malformed HTML
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $tds = $xpath->query('//td');
        $posts = [];

        // Map Italian month names to their corresponding numerical values
        $months = [
            'gennaio' => '01',
            'febbraio' => '02',
            'marzo' => '03',
            'aprile' => '04',
            'maggio' => '05',
            'giugno' => '06',
            'luglio' => '07',
            'agosto' => '08',
            'settembre' => '09',
            'ottobre' => '10',
            'novembre' => '11',
            'dicembre' => '12',
        ];

        foreach ($tds as $td) {
            $children = $td->childNodes;
            $currentPost = null;

            foreach ($children as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'div') {
                    $class = $child->getAttribute('class');

                    if ($class === 'separator') {
                        if ($currentPost && isset($currentPost['title'])) {
                            // Join all parts of content into one HTML string
                            $currentPost['content'] = implode('', $currentPost['content']);
                            $posts[] = $currentPost;
                            $currentPost = null;
                        }
                        continue;
                    }

                    if ($class === 'newstitle') {
                        if ($currentPost && isset($currentPost['title'])) {
                            // If another title appears without separator, treat previous post as complete
                            $currentPost['content'] = implode('', $currentPost['content']);
                            $posts[] = $currentPost;
                        }

                        $currentPost = [
                            'title' => trim($child->textContent),
                            'creation_date' => null,
                            'content' => []
                        ];
                    } elseif ($currentPost !== null) {
                        if ($class === 'newsdate') {
                            // Split the date into components
                            $dateParts = explode(' ', trim($child->textContent));
                            if (count($dateParts) === 3) {
                                $day = $dateParts[0];
                                $month = strtolower($dateParts[1]); // Convert month to lowercase
                                $year = $dateParts[2];

                                // Get the month number from the mapping
                                if (isset($months[$month])) {
                                    $monthNumber = $months[$month];
                                    // Create DateTime object
                                    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', "$year-$monthNumber-$day 00:00:00");
                                    if ($dateTime) {
                                        $currentPost['creation_date'] = $dateTime->format('Y-m-d H:i:s');
                                    }
                                }
                            }
                        } else {
                            // This is a generic content div (e.g., newstext, collegamentograndecentrato)
                            $html = $dom->saveHTML($child);
                            $currentPost['content'][] = $html;
                        }
                    }
                }
            }

            // In case the last post is not followed by a separator
            if ($currentPost && isset($currentPost['title'])) {
                $currentPost['content'] = implode('', $currentPost['content']);
                $posts[] = $currentPost;
            }
        }

        return $posts;
    }

    public function deriveCategories(string $htmlContent)
    {
        // Define categories and tags based on keywords
        $categories = [
            "Assemblee" => ['assemblea'],
            "Tornei" => ['campionato', 'tornei', 'torneino', 'torneo', 'slam', 'minislam', 'maratona blitz', 'Semilampo', 'provinciale', 'regionale', 'festival'],
            "Attività" => ['Pisa in gioco', 'Notte bianca', 'corsi', 'corso', 'scuola di scacchi', 'beach chess', 'iniziativa', 'conferenza', 'PisaCON', 'simultanea', 'Ingresso libero'],
            "Comunicazioni" => ['riapertura', 'chiusura', 'sospensione', 'ritrovo settimanale', 'incontro settimanale', 'ripresa attività', 'Riprendono gli incontri', 'assemblea', 'convocazione', 'nuova sede'],
            "Tesseramento" => ['tesseramento']
        ];

        // Extract text from HTML
        $text = strip_tags($htmlContent);
        $text = strtolower($text); // Normalize to lowercase

        $keywordCounts = [];

        // Count keywords for each category
        foreach ($categories as $category => $keywords) {
            $count = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $count++;
                }
            }
            if ($count > 0) {
                $keywordCounts[$category] = $count;
            }
        }

        // Determine the category with the most keywords
        $derivedCategories = [];
        if (!empty($keywordCounts)) {
            $maxCount = max($keywordCounts);
            foreach ($keywordCounts as $category => $count) {
                if ($count === $maxCount) {
                    $derivedCategories[] = $category; // Include all categories with the max count
                }
            }
        }

        return array_unique($derivedCategories);
    }

    public function setDryRun(bool $dryRun): ServiceInterface
    {
        $this->dryRun = $dryRun;
        return $this;
    }
}
