<?php

namespace DataDocumentBackup\Adapter\Twig;

use DataDocumentBackup\Contract\Data;
use DataDocumentBackup\Contract\TemplateReference;
use DataDocumentBackup\Exceptions\LoadingTemplateFailed;
use DataDocumentBackup\Exceptions\TemplateNotFound;
use DataDocumentBackup\Port\DocumentGenerator;
use DataDocumentBackup\Port\HtmlToPdfConverter;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

/**
 * Generates PDF documents from a twig template file
 *
 * The template folder must be provided to reduce risk of Arbitrary File Disclosure. To harden the system configure the respective php.ini directives for file inclusion.
 */
final class TwigTemplateDocumentGenerator implements DocumentGenerator
{
    /**
     * This is the path to base templates provided with the library.
     *
     * These templates may be overridden in a user defined template path. In this case the original files are available as `@default/{basename}.twig`.
     */
    private const PATH_TO_LIB_TEMPLATE_DIR = __DIR__ . '/../../../templates';

    /**
     * list of templates and namespaces to inject into twig loader
     *
     * var non-empty-array<string|int,string>
     */
    private readonly array $templatePaths;

    /**
     * keeps twig instance around for further transformations
     */
    private Environment|null $twig;

    /**
     * @param HtmlToPdfConverter $htmlToPdfConverter inject a service implementing a html to pdf conversion strategy
     * @param string $cacheDir Writable local filesystem path for twig to cache templates
     * @param array<string>|array<string,string> $userTemplatePaths list of paths to folders containing user-defined templates. optionally provide an array key to define a Twig namespace. This allows referencing a template with '@namespace/sub/path.twig'
     * @param array $extraTwigOptions See Twig documentation
     */
    public function __construct(
        private readonly HtmlToPdfConverter $htmlToPdfConverter,
        private readonly string $cacheDir,
        array $userTemplatePaths = [],
        private readonly array $extraTwigOptions = []
    ) {
        $defaultTemplateDir = realpath(self::PATH_TO_LIB_TEMPLATE_DIR);
        assert($defaultTemplateDir !== false, 'The path to the libraries template directory if wrong').

        $this->templatePaths = [
            ...$userTemplatePaths,
            'default' => $defaultTemplateDir,
            $defaultTemplateDir,
        ];
    }

    /**
     * @inheritDoc
     */
    public function generateFromTemplateWith(Data $data, TemplateReference $templateReference): string
    {
        $template = $this->loadTemplate($templateReference->templatePath);
        $templateData = [...$data->data, '_config' => $templateReference->config];
        $html = $template->render($templateData);
        return $this->createPdfFrom($html);
    }

    /**
     * Takes an HTML document and creates a PDF by rendering the page through the injected converter
     */
    private function createPdfFrom(string $html): string
    {
        return $this->htmlToPdfConverter->createPdfFrom($html);
    }

    private function loadTemplate(string $templatePath): TemplateWrapper
    {
        try {
            return $this->twig()->load($templatePath);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new TemplateNotFound("Failed to load template. Template does not exist or contains errors.", 0, $e);
        }
    }

    /**
     * Instantiate a twig environment based on the provided path configuration
     */
    private function initializeTwig(): Environment
    {
        try {

            $loader = new FilesystemLoader();

            foreach ($this->templatePaths as $namespace => $path) {
                $loader->addPath($path, is_string($namespace) ? $namespace : FilesystemLoader::MAIN_NAMESPACE);
            }

            $twig = new Environment($loader, [
                'cache' => $this->cacheDir,
                ...$this->extraTwigOptions
            ]);

            // The StringLoaderExtension makes the template_from_string function available:
            $twig->addExtension(new StringLoaderExtension());
            return $twig;

        } catch (LoaderError $e) {
            throw new LoadingTemplateFailed("Failed to load template. Twig not configured correctly.", 0, $e);
        }
    }

    private function twig(): Environment
    {
        if (!(isset($this->twig) && $this->twig instanceof Environment)) {
            $this->twig = $this->initializeTwig();
        }
        return $this->twig;
    }
}