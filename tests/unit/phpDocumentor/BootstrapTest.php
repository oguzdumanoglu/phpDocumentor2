<?php
/**
 * phpDocumentor
 *
 * PHP Version 5.3
 *
 * @copyright 2010-2018 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * Test class for \phpDocumentor\Bootstrap.
 *
 * @covers phpDocumentor\Bootstrap
 */
class BootstrapTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    /**
     * Directory structure when phpdocumentor is installed using composer.
     *
     * @var array
     */
    protected $composerInstalledStructure = [
        'dummy' => [
            'vendor' => [
                'phpDocumentor' => [
                    'phpDocumentor' => [
                        'src' => [
                            'phpDocumentor' => [],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * Directory structure when phpdocumentor is installed from git.
     *
     * @var array
     */
    protected $standaloneStructure = [
        'dummy' => [
            'vendor' => [],
            'src' => [
                'phpDocumentor' => [],
            ],
            'test' => [],
        ],
    ];

    /**
     * @covers phpDocumentor\Bootstrap::createInstance
     */
    public function testCreatingAnInstanceUsingStaticFactoryMethod()
    {
        $this->assertInstanceOf('phpDocumentor\Bootstrap', Bootstrap::createInstance());
    }

    /**
     * @covers phpDocumentor\Bootstrap::initialize
     */
    public function testInitializingTheApplication()
    {
        $bootstrap = Bootstrap::createInstance();
        $this->assertInstanceOf('phpDocumentor\Application', $bootstrap->initialize());
    }

    /**
     * @covers phpDocumentor\Bootstrap::findVendorPath
     */
    public function testFindVendorPathStandAloneInstall()
    {
        vfsStream::setup('root', null, $this->standaloneStructure);
        $bootstrap = Bootstrap::createInstance();

        $baseDir = vfsStream::url('root/dummy/src/phpDocumentor');
        $this->assertSame('vfs://root/dummy/src/phpDocumentor/../../vendor', $bootstrap->findVendorPath($baseDir));
    }

    /**
     * @covers phpDocumentor\Bootstrap::findVendorPath
     */
    public function testFindVendorPathComposerInstalled()
    {
        $root = vfsStream::setup('root', null, $this->composerInstalledStructure);
        vfsStream::newFile('composer.json')->at($root->getChild('dummy'));

        $bootstrap = Bootstrap::createInstance();
        $baseDir = vfsStream::url('root/dummy/vendor/phpDocumentor/phpDocumentor/src/phpDocumentor');
        $this->assertSame(
            'vfs://root/dummy/vendor/phpDocumentor/phpDocumentor/src/phpDocumentor/../../../../../vendor',
            $bootstrap->findVendorPath($baseDir)
        );
    }

    /**
     * Tests if exception is thrown when no autoloader is present
     *
     * @expectedException \RuntimeException
     * @covers phpDocumentor\Bootstrap::createAutoloader
     */
    public function testCreateAutoloaderNoAutoloader()
    {
        vfsStream::setup('root', null, $this->standaloneStructure);
        $bootstrap = Bootstrap::createInstance();
        $bootstrap->createAutoloader(vfsStream::url('root/dummy/vendor'));
    }

    /**
     * checks autoload.php is required and returned by createAutoloader
     *
     * @covers phpDocumentor\Bootstrap::createAutoloader
     */
    public function testCreateAutoloader()
    {
        $root = vfsStream::setup('root', null, $this->standaloneStructure);

        /** @var vfsStreamDirectory $firstChild */
        $firstChild = $root->getChild('dummy');
        $secondChild = $firstChild->getChild('vendor');

        vfsStream::newFile('autoload.php')->withContent('<?php return true;')
            ->at($secondChild);

        $bootstrap = Bootstrap::createInstance();
        $this->assertTrue($bootstrap->createAutoloader(vfsStream::url('root/dummy/vendor')));
    }
}
