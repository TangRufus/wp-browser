<?php
namespace Codeception\Module;


use Codeception\Exception\ModuleConfigException;
use Codeception\Lib\ModuleContainer;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Prophecy\Argument;
use tad\WPBrowser\Environment\Executor;

class WPCLITest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var ModuleContainer
     */
    protected $moduleContainer;

    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    /**
     * @var Executor
     */
    protected $executor;

    /**
     * @var array
     */
    protected $config;

    /**
     * @test
     * it should be instantiatable
     */
    public function it_should_be_instantiatable()
    {
        $sut = $this->make_instance();

        $this->assertInstanceOf(WPCLI::class, $sut);
    }

    /**
     * @return mixed
     */
    private function make_instance()
    {
        return new WPCLI($this->moduleContainer->reveal(), $this->config, $this->executor->reveal());
    }

    /**
     * @test
     * it should throw if path is not folder
     */
    public function it_should_throw_if_path_is_not_folder()
    {
        $this->config = ['path' => '/some/path/to/null'];

        $this->expectException(ModuleConfigException::class);

        $this->make_instance();
    }

    /**
     * @test
     * it should call the executor with proper parameters
     */
    public function it_should_call_the_executor_with_proper_parameters()
    {
        $this->executor->exec(Argument::containingString('--path=' . $this->root->url() . '/wp'), Argument::any(), Argument::any())->shouldBeCalled();
        $this->executor->exec(Argument::containingString('core version'), Argument::any(), Argument::any())->shouldBeCalled();

        $sut = $this->make_instance();

        $sut->cli('core version');
    }

    public function optionalOptionsWithArguments()
    {
        return [
            ['ssh', 'some-ssh'],
            ['http', 'some-http'],
            ['url', 'some-url'],
            ['user', 'some-user'],
            ['skip-plugins', 'some-plugin, another-plugin'],
            ['skip-themes', 'some-theme, another-theme'],
            ['skip-packages', 'some-package, another-package'],
            ['require', 'some-file']
        ];
    }

    /**
     * @test
     * it should allow setting additional wp-cli options in the config file
     * @dataProvider optionalOptionsWithArguments
     */
    public function it_should_allow_setting_additional_wp_cli_options_in_the_config_file($option, $optionValue)
    {
        $this->config[$option] = $optionValue;
        $this->executor->exec(Argument::containingString('--' . $option . '=' . $optionValue), Argument::any(), Argument::any())->shouldBeCalled();

        $sut = $this->make_instance();

        $sut->cli('core version');
    }

    /**
     * @test
     * it should set debug paramter by default
     */
    public function it_should_set_debug_paramter_by_default()
    {
        $this->executor->exec(Argument::containingString('--debug'), Argument::any(), Argument::any())->shouldBeCalled();

        $sut = $this->make_instance();

        $sut->cli('core version');
    }

    public function skippedOptions()
    {
        return [
            ['color'],
            ['prompt'],
            ['quiet']
        ];
    }

    /**
     * @test
     * it should skip some options by default
     * @dataProvider skippedOptions
     */
    public function it_should_skip_some_options_by_default($option)
    {
        $this->config[$option] = true;
        $this->executor->exec(Argument::not(Argument::containingString('--' . $option)), Argument::any(), Argument::any())->shouldBeCalled();

        $sut = $this->make_instance();

        $sut->cli('core version');
    }

    protected function _before()
    {
        $this->moduleContainer = $this->prophesize(ModuleContainer::class);
        $this->root = vfsStream::setup('root');
        $wpDir = vfsStream::newDirectory('wp');
        $this->root->addChild($wpDir);
        $this->config = ['path' => $this->root->url() . '/wp'];
        $this->executor = $this->prophesize(Executor::class);
    }

    protected function _after()
    {
    }
}
