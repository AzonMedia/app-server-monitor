<?php
declare(strict_types=1);

namespace GuzabaPlatform\AppServer\Monitor\Controllers;

use Guzaba2\Http\Method;
use Guzaba2\Swoole\IpcRequest;
use Guzaba2\Swoole\Server;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Monitor extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/app-server-monitor' => [
                Method::HTTP_GET_HEAD_OPT => [self::class, 'main']
            ],
            '/admin/crud-objects/{class_name}/{page}/{limit}/{search_values}/{sort_by}/{sort_desc}' => [
                Method::HTTP_GET_HEAD_OPT => [self::class, 'objects']
            ],
        ],
        'services'      => [
            'Server'
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * @return ResponseInterface
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function main(): ResponseInterface
    {
        /** @var Server $Server */
        $Server = self::get_service('Service');
        $IpcRequest = new IpcRequest(Method::HTTP_GET, '/admin/app-server-monitor/worker-info-provider');
        $responses = $Server->send_broadcast_ipc_request($IpcRequest);

    }

}