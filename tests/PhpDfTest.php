<?php

namespace Bigdanhe\Phpdf\Tests;

use PHPUnit\Framework\TestCase;
use Bigdanhe\Phpdf\PhpDf;
use InvalidArgumentException;

class PhpDfTest extends TestCase
{
    private PhpDf $pdfGenerator;
    
    protected function setUp(): void
    {
        $this->pdfGenerator = new PhpDf('mpdf');
    }
    
    public function testConstructorWithValidEngine(): void
    {
        $mpdfGenerator = new PhpDf('mpdf');
        $this->assertEquals('mpdf', $mpdfGenerator->getEngine());
        
        $tcpdfGenerator = new PhpDf('tcpdf');
        $this->assertEquals('tcpdf', $tcpdfGenerator->getEngine());
    }
    
    public function testConstructorWithInvalidEngine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PhpDf('invalid_engine');
    }
    
    public function testSetPaperWithValidFormat(): void
    {
        $result = $this->pdfGenerator->setPaper('A4', 'portrait');
        $this->assertInstanceOf(PhpDf::class, $result);
    }
    
    public function testSetPaperWithInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pdfGenerator->setPaper('InvalidFormat');
    }
    
    public function testSetPaperWithCustomSizeWithoutDimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pdfGenerator->setPaper('CUSTOM');
    }
    
    public function testSetDpiWithValidValue(): void
    {
        $result = $this->pdfGenerator->setDpi(300);
        $this->assertInstanceOf(PhpDf::class, $result);
    }
    
    public function testSetDpiWithInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->pdfGenerator->setDpi(500);
    }
    
    public function testLoadHtmlWithValidInput(): void
    {
        $result = $this->pdfGenerator->loadHtml('<h1>Test</h1>');
        $this->assertInstanceOf(PhpDf::class, $result);
    }
    
    public function testLoadHtmlWithCss(): void
    {
        $result = $this->pdfGenerator->loadHtml(
            '<h1>Test</h1>',
            'h1 { color: red; }'
        );
        $this->assertInstanceOf(PhpDf::class, $result);
    }
    
    public function testRenderWithoutPath(): void
    {
        $result = $this->pdfGenerator
            ->loadHtml('<h1>Test</h1>')
            ->render();
            
        $this->assertIsString($result);
        $this->assertStringStartsWith('%PDF-', $result);
    }
    
    public function testRenderWithPath(): void
    {
        $outputPath = sys_get_temp_dir() . '/test.pdf';
        
        $result = $this->pdfGenerator
            ->loadHtml('<h1>Test</h1>')
            ->render($outputPath);
            
        $this->assertTrue($result);
        $this->assertFileExists($outputPath);
        
        unlink($outputPath);
    }
    
    /**
     * @dataProvider paperFormatProvider
     */
    public function testPaperFormats(string $format, string $orientation): void
    {
        $result = $this->pdfGenerator
            ->setPaper($format, $orientation)
            ->loadHtml('<h1>Test</h1>')
            ->render();
            
        $this->assertIsString($result);
    }
    
    public function paperFormatProvider(): array
    {
        return [
            ['A4', 'portrait'],
            ['A3', 'landscape'],
            ['LETTER', 'portrait'],
            ['LEGAL', 'landscape'],
            ['CUSTOM', 'portrait', [100, 100]],
        ];
    }
}