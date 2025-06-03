<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;

class DocumentationController extends Controller
{
    /**
     * Display the documentation index.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->show('index.md');
    }

    /**
     * Display a documentation file.
     *
     * @param  string  $filename
     * @return \Illuminate\Http\Response
     */
    public function show($filename)
    {
        $path = base_path('docs/' . $filename);
        
        if (!File::exists($path)) {
            abort(404, 'Documentation file not found');
        }
        
        $content = File::get($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        if ($extension === 'md') {
            // Configure the CommonMark environment with extensions
            $config = [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 100,
                'heading_permalink' => [
                    'html_class' => 'heading-permalink',
                    'id_prefix' => 'user-content',
                    'fragment_prefix' => 'user-content-',
                    'symbol' => '#',
                    'insert' => 'after',
                ],
            ];
            
            $environment = new Environment($config);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new TableExtension());
            $environment->addExtension(new AutolinkExtension());
            $environment->addExtension(new StrikethroughExtension());
            $environment->addExtension(new HeadingPermalinkExtension());
            
            $converter = new MarkdownConverter($environment);
            $html = $converter->convert($content)->getContent();
            
            // Process internal links to make them work correctly
            $html = $this->processInternalLinks($html, $filename);
            
            // Get the title from the first h1 tag or use the filename
            $title = $this->extractTitle($content) ?: ucfirst(str_replace(['-', '_', '.md'], [' ', ' ', ''], $filename));
            
            return view('documentation', [
                'title' => $title,
                'content' => $html,
                'filename' => $filename
            ]);
        }
        
        // For non-markdown files, just return the content
        return response($content, 200);
    }
    
    /**
     * Extract the title from the markdown content.
     *
     * @param  string  $content
     * @return string|null
     */
    private function extractTitle($content)
    {
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Process internal links to make them work correctly.
     *
     * @param  string  $html
     * @param  string  $currentFile
     * @return string
     */
    private function processInternalLinks($html, $currentFile)
    {
        // Convert relative markdown links to proper routes
        return preg_replace_callback(
            '/<a href="([^"]+\.md)">/',
            function ($matches) {
                $link = $matches[1];
                return '<a href="' . route('documentation.show', $link) . '">';
            },
            $html
        );
    }
}
