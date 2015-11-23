<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 15/1/30
 * Time: 上午11:18
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 */

namespace FastD\Framework\Bundle\Events\Http;

use FastD\Framework\Bundle\Events\ContainerAware;
use FastD\Framework\Bundle\Events\EventInterface;
use FastD\Http\Session\Storage\RedisStorage;
use FastD\Http\Session\Session;
use FastD\Http\Session\SessionHandler;
use FastD\Database\Database;
use FastD\Http\RedirectResponse;
use FastD\Storage\StorageManager;
use FastD\Http\Response;
use FastD\Http\JsonResponse;
use FastD\Http\XmlResponse;

/**
 * Class Event
 *
 * @package FastD\Framework\Bundle\Events\Http
 */
class Event extends ContainerAware implements EventInterface
{
    const SERVER_NAME = 'FastD';
    const SERVER_VERSION = '2.0';

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var StorageManager
     */
    protected $storage;

    /**
     * @var Session
     */
    protected $session;

    /**
     * Get custom defined helper obj.
     *
     * @param string $helper
     * @param array $parameters
     * @param bool $newInstance
     * @return mixed
     */
    public function get($helper, array $parameters = array(), $newInstance = false)
    {
        return $this->container->get($helper, $parameters, $newInstance);
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        if ($this->session instanceof Session) {
            return $this->session;
        }

        $config = $this->getParameters('session');

        $storage = new RedisStorage($config['host'], $config['port'], isset($config['auth']) ? $config['auth'] : null);

        $handler = new SessionHandler($storage);

        $this->session = $this->get('kernel.request')->getSessionHandle($handler);

        unset($storage, $handler);

        return $this->session;
    }

    public function getConnection($connection = null, array $options = [])
    {
        if (null === $this->database) {
            $this->database = $this->get('kernel.database', [$this->getParameters('database')]);
        }

        return $this->database->getConnection($connection);
    }

    public function getStorage($connection, array $options = [])
    {
        if (null === $this->storage) {
            $this->storage = $this->get('kernel.storage', [$this->getParameters('storage')]);
        }

        return $this->storage->getConnection($connection);
    }

    /**
     * Get custom config parameters.
     *
     * @param string $name
     * @return mixed
     */
    public function getParameters($name = null)
    {
        return $this->get('kernel.config')->get($name);
    }

    /**
     * @param       $name
     * @param array $parameters
     * @param string$format
     * @return string
     */
    public function generateUrl($name, array $parameters = array(), $format = '')
    {
        $url = $this->get('kernel.routing')->generateUrl($name, $parameters, $format);
        if ('http' !== substr($url, 0, 4)) {
            $url = ('/' === ($path = $this->get('kernel.request')->getBaseUrl()) ? '' : $path) . $url;
            $url = str_replace('//', '/', $url);
        }

        return $this->get('kernel.request')->getSchemeAndHttpAndHost() . $url;
    }

    /**
     * @param      $name
     * @return string
     */
    public function asset($name, $verion = null)
    {
    }

    public function redirect($url, array $parameters = [], $statusCode = 302, array $headers = [])
    {
        return new RedirectResponse($url, $statusCode, $headers);
    }

    /**
     * @param       $name
     * @param array $parameters
     * @return  mixed
     */
    public function forward($name, array $parameters = [])
    {

    }

    public function render($template, array $parameters = array())
    {
        $paths = $this->getParameters('template.paths');
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            $paths[] = dirname($bundle->getRootPath());
        }
        $options = [];
        if (!($isDebug = $this->container->get('kernel')->isDebug())) {
            $options = [
                'cache' => $this->getParameters('template.cache'),
                'debug' => $isDebug,
            ];
        }
        $self = $this;
        $this->template = $this->container->get('kernel.template', [$paths, $options]);
        $this->template->addGlobal('request', $this->getRequest());
        $this->template->addFunction(new \Twig_SimpleFunction('url', function ($name, array $parameters = [], $format = '') use ($self) {
            return $self->generateUrl($name, $parameters, $format);
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('asset', function ($name, $host = null, $path = null) use ($self) {
            return $self->asset($name, $host, $path);
        }));
        unset($paths, $options);

        return $this->template->render($template, $parameters);
    }

    /**
     * @param       $data
     * @param int   $status
     * @param array $headers
     * @return JsonResponse|Response|XmlResponse
     */
    public function response($data, $status = Response::HTTP_OK, array $headers = [])
    {
        switch ($this->get('kernel.request')->getFormat()) {
            case 'json':
                return $this->responseJson($data, $status, $headers);
            case 'xml':
                return $this->responseXml($data, $status, $headers);
            case 'html':
            case 'text':
            case 'php':
            default:
                return $this->responseHtml($data, $status, $headers);
        }
    }

    /**
     * @param array $data
     * @param int   $status
     * @param array $headers
     * @return XmlResponse
     */
    public function responseXml(array $data, $status = Response::HTTP_OK, array $headers = [])
    {
        return new XmlResponse($data, $status, $headers);
    }

    /**
     * @param       $data
     * @param int   $status
     * @param array $headers
     * @return Response
     */
    public function responseHtml($data, $status = Response::HTTP_OK, array $headers = [])
    {
        return new Response($data, $status, $headers);
    }

    /**
     * @param array $data
     * @param int   $status
     * @param array $headers
     * @return JsonResponse
     */
    public function responseJson(array $data, $status = Response::HTTP_OK, array $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }
}