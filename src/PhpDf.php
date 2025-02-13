<?php

namespace Bigdanhe\Phpdf;

use Mpdf\Mpdf;
use TCPDF;
use Sabberworm\CSS\Parser as CssParser;
use RuntimeException;
use InvalidArgumentException;

class PhpDf
{
    private $engine;
    private string $selectedEngine;
    
    private const SUPPORTED_ENGINES = ['mpdf', 'tcpdf'];
    private const SUPPORTED_FORMATS = ['A4', 'A3', 'LETTER', 'LEGAL', 'CUSTOM'];
    
    public function __construct(string $engine = 'mpdf', array $config = [])
    {
        if (!in_array($engine, self::SUPPORTED_ENGINES)) {
            throw new InvalidArgumentException(
                sprintf('Engine must be one of: %s', implode(', ', self::SUPPORTED_ENGINES))
            );
        }
        
        $this->selectedEngine = $engine;
        $this->initEngine($config);
    }
    
    private function initEngine(array $config): void
    {
        switch ($this->selectedEngine) {
            case 'mpdf':
                $defaultConfig = [
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'default_font' => 'dejavusans'
                ];
                $this->engine = new Mpdf(array_merge($defaultConfig, $config));
                break;
                
            case 'tcpdf':
                $this->engine = new TCPDF(
                    $config['orientation'] ?? 'P',
                    $config['unit'] ?? 'mm',
                    $config['format'] ?? 'A4',
                    $config['unicode'] ?? true,
                    $config['encoding'] ?? 'UTF-8'
                );
                $this->engine->SetCreator($config['creator'] ?? 'PhpDf');
                break;
        }
    }
    
    public function setPaper(string $format = 'A4', string $orientation = 'portrait', ?array $customSize = null): self
    {
        if (!in_array($format, self::SUPPORTED_FORMATS)) {
            throw new InvalidArgumentException(
                sprintf('Format must be one of: %s', implode(', ', self::SUPPORTED_FORMATS))
            );
        }
        
        if ($format === 'CUSTOM' && !$customSize) {
            throw new InvalidArgumentException('Custom size array required when using CUSTOM format');
        }
        
        if ($this->selectedEngine === 'mpdf') {
            $format = $format === 'CUSTOM' ? $customSize : $format;
            $this->engine->SetDisplayMode('fullpage');
            $this->engine->AddPage($orientation, $format);
        } else {
            $this->engine->AddPage(
                $orientation === 'portrait' ? 'P' : 'L',
                $format === 'CUSTOM' ? $customSize : $format
            );
        }
        
        return $this;
    }
    
    public function setDpi(int $dpi): self
    {
        if ($dpi < 72 || $dpi > 300) {
            throw new InvalidArgumentException('DPI must be between 72 and 300');
        }
        
        if ($this->selectedEngine === 'mpdf') {
            $this->engine->img_dpi = $dpi;
        } else {
            $this->engine->setImageScale($dpi / 72);
        }
        
        return $this;
    }
    
    public function loadHtml(string $html, ?string $css = null): self
    {
        if ($css) {
            $cssParser = new CssParser($css);
            $cssDocument = $cssParser->parse();
            
            $html = sprintf(
                '<html><head><style>%s</style></head><body>%s</body></html>',
                $cssDocument->render(),
                $html
            );
        }
        
        if ($this->selectedEngine === 'mpdf') {
            $this->engine->WriteHTML($html);
        } else {
            $this->engine->writeHTML($html);
        }
        
        return $this;
    }
    
    public function render(?string $outputPath = null): string|bool
    {
        try {
            if ($this->selectedEngine === 'mpdf') {
                if ($outputPath) {
                    $this->engine->Output($outputPath, 'F');
                    return true;
                }
                return $this->engine->Output('', 'S');
            } else {
                if ($outputPath) {
                    return $this->engine->Output($outputPath, 'F');
                }
                return $this->engine->Output('', 'S');
            }
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to render PDF: ' . $e->getMessage(), 0, $e);
        }
    }
    
    public function stream(string $filename = 'document.pdf'): void
    {
        if ($this->selectedEngine === 'mpdf') {
            $this->engine->Output($filename, 'D');
        } else {
            $this->engine->Output($filename, 'D');
        }
    }
    
    public function getEngine(): string
    {
        return $this->selectedEngine;
    }
}

