<?php
/**
 * Test class for Dynamic404 System Plugin
 *
 * @author     Jisse Reitsma <jisse@yireo.com>
 * @copyright  Copyright 2016 Jisse Reitsma
 * @license    GNU Public License version 3 or later
 * @link       https://www.yireo.com/
 */

use Yireo\Test\PluginCase;

/**
 * Class PlgSystemAutoLoginIpTest
 */
class PlgSystemDynamic404Test extends PluginCase
{
	/**
	 * @var string
	 */
	protected $pluginName = 'dynamic404';

	/**
	 * @var string
	 */
	protected $pluginGroup = 'system';

	/**
	 * @var array
	 */
	protected $pluginParams = [];

	/**
	 * @return void
	 */
	public function testGetRedirectUrl()
	{
		$plugin = $this->getPluginInstance();
		$method = $this->getObjectMethod($plugin, 'includeLibrary');
		$this->assertTrue($method->invokeArgs($plugin, []));
	}
}

