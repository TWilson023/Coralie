<?php

namespace Coralie\Traits;

/**
 * @author Tim Wilson
 * @copyright 2017 Aranode LLC
 *
 * Utility singleton trait
 */

trait Singleton
{
	// TODO:  Consider moving this trait into the framework itself.

	protected static $instance;

	/**
	 * @param mixed ...$params Parameters to pass to the singleton constructor.
	 *
	 * @return static An instance of the singleton.
	 */
	public static final function getInstance(...$params)
	{
		return static::$instance ?? (static::$instance = new static(...$params));
	}

	/**
	 * Singleton constructor.
	 *
	 * @param array ...$params Parameters to be passed to the init() method.
	 */
	private final function __construct(...$params)
	{
		$this->init(...$params);
	}

	/**
	 * This method may be overridden by the singleton class to be run upon
	 * instantiation.
	 */
	protected function init(): void {}
}