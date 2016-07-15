<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/6/30
 * Time: 下午5:15
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 * WebSite: http://www.janhuang.me
 */

namespace FastD\Framework\Kernel;

use FastD\Config\Config;
use FastD\Container\Container;
use FastD\Framework\Bundle\Bundle;

/**
 * Interface AppKernelInterface
 *
 * @package FastD\Framework\Kernel
 */
interface AppKernelInterface
{
    /**
     * Run framework into bootstrap file.
     *
     * @param $bootstrap
     * @return mixed
     */
    public static function run($bootstrap);

    /**
     * @return Bundle[]
     */
    public function getBundles();

    /**
     * @return Container
     */
    public function getContainer();

    /**
     * Bootstrap Application Container.
     *
     * @return void
     */
    public function bootstrap();
}