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
        $struct = [];
        /** @var Server $Server */
        $Server = self::get_service('Server');
        $IpcRequest = new IpcRequest(Method::HTTP_GET, '/api/admin/app-server-monitor/worker-info');
        $ipc_responses = $Server->send_broadcast_ipc_request($IpcRequest);
        foreach ($ipc_responses as $IpcResponse) {
            if ($IpcResponse) {
                $resp_struct = $IpcResponse->getBody()->getStructure();
            } else {
                $resp_struct = 'no data received';
            }

            //$struct['workers'][$resp_struct['general']['worker_id']] = $resp_struct;
            $struct['workers'][] = $resp_struct;
        }
        //we need to add the current worker data
        $struct['workers'][] = $this->execute_controller_action_structured(Responder::class, 'worker_info');

        $struct['general'] = [
            'responding_worker_id'  => $Server->get_worker_id(),//the worker ID that sent this response
            'master_pid'            => $Server->get_master_pid(),
            'manager_pid'           => $Server->get_manager_pid(),
            'options'               => $Server->get_all_options(),
        ];

        return self::get_structured_ok_response($struct);
    }

}