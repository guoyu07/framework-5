<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/8/2
 * Time: 上午12:13
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 * WebSite: http://www.janhuang.me
 */

namespace FastD\Framework\Bundle\Commands;

use FastD\Console\Command\Command;
use FastD\Console\IO\Input;
use FastD\Console\IO\Output;
use FastD\Console\IO\OutputInterface;

class RouteCacheCommand extends Command
{
    const CACHE_NAME = 'routes.cache';

    public function getName()
    {
        return 'route:cache';
    }

    public function configure(){}

    public function execute(Input $input, Output $output)
    {
        $kernel = $this->getApplication()->getKernel();

        $routing = $kernel->getContainer()->singleton('kernel.routing');
        $caching = $kernel->getRootPath() . DIRECTORY_SEPARATOR . RouteCacheCommand::CACHE_NAME;
        // Init caching file.
        file_put_contents($caching, '<?php' . PHP_EOL);

        foreach ($routing as $route) {

            $default = array() === $route->getDefaults() ? '[]' : (function () use ($route) {
                $arr = [];
                foreach ($route->getDefaults() as $name => $value) {
                    $arr[] = "'{$name}' => '$value'";
                }
                return '[' . implode(',', $arr). ']';
            })();

            $requirements = array() === $route->getRequirements() ? '[]' : (function () use ($route) {
                $arr = [];
                foreach ($route->getRequirements() as $name => $value) {
                    $arr[] = "'{$name}' => '$value'";
                }
                return '[' . implode(',', $arr). ']';
            })();

            $line = "Routes::" . strtolower($route->getMethod()) . "'({$route->getName()}', '{$route->getPath()}', '{$route->getCallback()}', {$default}, {$requirements})";

            if (null != $route->getHost()) {
                $routeCaching .= '->setHost([\'' . implode('\',\'', $route->getHost() ?? []) .'\'])';
            }
            if (null != $route->getScheme() && $route->getScheme() != ['http']) {
                $routeCaching .= '->setSchema([\'' . implode('\',\'', $route->getSchema() ?? []) .'\'])';
            }
            if (null != $route->getFormats()) {
                $routeCaching .= '->setFormats([\'' . implode('\',\'', $route->getFormats() ?? []) . '\'])';
            }

            // Routes::match();
            file_put_contents($caching, $line . ';' . PHP_EOL, FILE_APPEND);
        }
        $output->write('Caching to ' . $caching . '......');
        $output->writeln('    [OK]', OutputInterface::STYLE_SUCCESS);
        return 1;
    }
}