<?php

use MediaWiki\MediaWikiServices;

class PDFjsEmbed
{
    /**
     * @param Parser $parser
     */
    public static function onParserFirstCallInit(Parser $parser)
    {
        // Enables the <pdfjs> tag.
        $parser->setHook('pdfjs', [self::class, 'pdfjs']);

        global $wgOut, $wgResourceBasePath;

        $wgOut->addScript('<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.6.347/pdf.min.js"></script>' . "\n");
        $wgOut->addScript('<script type="text/javascript" src="' . $wgResourceBasePath . '/extensions/PDFjsEmbed/resources/pdfjsembed.js"></script>' . "\n");
    }

    /**
     * disable the cache
     *
     * @param Parser $parser
     */
    static public function disableCache(Parser $parser)
    {
        // see https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/MagicNoCache/+/refs/heads/master/src/MagicNoCacheHooks.php
        global $wgOut;
        $parser->getOutput()->updateCacheExpiry(0);
        $wgOut->enableClientCache(false);
    }

    /**
     * Parser hook for the <pdfjs> tag.
     *
     * @param ?string $text Raw, untrimmed wikitext content of the <pdfjs> tag, if any
     * @param string[] $argv
     * @param Parser $parser
     * @param PPFrame $frame
     *
     * @return string HTML
     */
    public static function pdfjs(
        ?string $text,
        array $argv,
        Parser $parser,
        PPFrame $frame
    ): string {
        // disable the cache
        self::disableCache($parser);

        // grab the uri by parsing to html
        $html = $parser->recursiveTagParse($text, $frame);

        if (preg_match('([^\/]+\.pdf)', $html, $matches)) {
            # $matches contains the groups
            $filename = $matches[0];
            $pdfFile = MediaWikiServices::getInstance()->getRepoGroup()->findFile($filename);

            if ($pdfFile !== false) {
                $url = $pdfFile->getFullUrl();
                return self::embed($url);
            } else {
                return self::error('embed_pdf_invalid_file', $html);
            }
        } else {
            return self::error('embed_pdf_invalid_file', $html);
        }
    }

    /**
     * Returns an HTML node for the given file as string.
     *
     * @access private
     * @param
     *            URL url to embed.
     * @return string HTML code for canvas.
     */
    static private function embed($url)
    {
        return Html::rawElement('canvas', [
            'data-url' => $url,
            'class' => 'pdfjs'
        ]);
    }

    /**
     * Returns a standard error message.
     *
     * @access private
     * @param
     *            string Error message key to display.
     * @param
     *            params any parameters for the error message
     * @return string HTML error message.
     */
    private static function error($messageKey, ...$params)
    {
        return Xml::span(wfMessage($messageKey, $params)->plain(), 'error');
    }
}
