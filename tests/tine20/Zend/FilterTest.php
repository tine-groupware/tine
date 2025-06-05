<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

//require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Zend_FilterTest extends \PHPUnit\Framework\TestCase
{
    public function testFilter(): void
    {
        $inputFilter = new Zend_Filter_Input([
            'unittest' => [
                [Zend_Filter_Empty::class, 'result'],
            ],
        ], [], [], [Zend_Filter_Input::FILTER_ARRAYS_FLAT => true]);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame([], $inputFilter->getUnescaped());

        $inputFilter->setData(['unittest' => null]);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame(['unittest' => 'result'], $inputFilter->getUnescaped());

        $inputFilter->setData(['unittest' => ['unittest' => null]]);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame(['unittest' => ['unittest' => null]], $inputFilter->getUnescaped());

        $inputFilter = new Zend_Filter_Input([
            'unittest' => [
                [Zend_Filter_Empty::class, 'result'],
            ],
        ], [], ['unittest' => ['unittest' => null]]);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame(['unittest' => ['unittest' => 'result']], $inputFilter->getUnescaped());
    }

    public function testValidation(): void
    {
        $inputFilter = new Zend_Filter_Input([], [
            'unittest' => [
                Zend_Filter_Input::DEFAULT_VALUE => 'result',
            ]
        ], []);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame(['unittest' => 'result'], $inputFilter->getUnescaped());

        $inputFilter = new Zend_Filter_Input([], [
            'unittest' => [
                Zend_Filter_Input::DEFAULT_VALUE => ['sprintf', 'result'],
            ]
        ], []);
        $this->assertTrue($inputFilter->isValid());
        $this->assertSame(['unittest' => 'result'], $inputFilter->getUnescaped());
    }
}