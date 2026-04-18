<?php
/**
 * Main plugin bootstrap.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

use Coordina\Platform\Kernel;

final class Plugin {
	/**
	 * Platform kernel.
	 *
	 * @var Kernel
	 */
	private $kernel;

	/**
	 * Constructor.
	 *
	 * @param Kernel|null $kernel Optional kernel.
	 */
	public function __construct( ?Kernel $kernel = null ) {
		$this->kernel = $kernel ?: new Kernel();
	}

	/**
	 * Boot the plugin.
	 */
	public function boot(): void {
		$this->kernel->boot();
	}

	/**
	 * Expose the shared container for backward compatibility.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->kernel->container();
	}
}
