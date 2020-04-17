<?php
declare(strict_types=1);

namespace GuzabaPlatform\AppServer\Monitor\Controllers;

use Guzaba2\Authorization\CurrentUser;
use Guzaba2\Coroutine\Coroutine;
use Guzaba2\Http\Method;
use Guzaba2\Http\StatusCode;
use Guzaba2\Swoole\IpcRequest;
use Guzaba2\Swoole\Server;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Monitor extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/app-server-monitor'                     => [
                Method::HTTP_GET                                => [self::class, 'main']
            ],
            '/admin/app-server-monitor/clear-orm-cache'     => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                               => [self::class, 'objects']
            ],
            '/admin/app-server-monitor/clear-query-cache'   => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                               => [self::class, 'objects']
            ],
            '/admin/app-server-monitor/trigger-gc'          => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                               => [self::class, 'objects']
            ],
            '/admin/app-server-monitor/set-memory-limit'    => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                               => [self::class, 'set_memory_limit']
            ],

        ],
        'services'      => [
            'Server',
            'CurrentUser',//needed for localization
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

        $struct['workers'] = $this->execute_broadcast_request(Method::HTTP_GET, '/api/admin/app-server-monitor/worker/info', Responder::class, 'worker_info');

        /** @var CurrentUser $CurrentUser */
        $CurrentUser = self::get_service('CurrentUser');

        $struct['general'] = [
            'responding_worker_id'              => $Server->get_worker_id(),//the worker ID that sent this response
            'responding_cid'                    => Coroutine::getCid(),
            'master_pid'                        => $Server->get_master_pid(),
            'manager_pid'                       => $Server->get_manager_pid(),
            'options'                           => $Server->get_all_options(),
            'server_start_microtime'            => $Server->get_start_microtime(),
            'server_start_formatted_time'       => date( $CurrentUser->get()->get_date_time_format(), (int) $Server->get_start_microtime() ),
        ];

        return self::get_structured_ok_response($struct);
    }

    /**
     *
     * The garbage collector can be triggered but this may not bring memory savings as the caches are stored in arrays not objects.
     * @param float $percentage
     * @param array $worker_ids If empty array is provided it applies to all workers
     * @param bool $trigger_gc Should the garbage collector be triggered after the cleanup
     * @return ResponseInterface
     */
    public function clear_orm_cache(float $percentage = 100, bool $trigger_gc = TRUE, array $worker_ids = []): ResponseInterface
    {

    }

    /**
     * @param float $percentage
     * @param bool $trigger_gc
     * @param array $worker_ids
     * @return ResponseInterface
     */
    public function clear_query_cache(float $percentage = 100, bool $trigger_gc = TRUE, array $worker_ids = []): ResponseInterface
    {

    }

    public function trigger_gc(array $worker_ids = []): ResponseInterface
    {

    }

    /**
     * Clears Orm & Query cache and optionally calls the GC.
     * @param bool $trigger_gc
     * @param array $worker_ids
     * @return ResponseInterface
     */
    public function clear_all_caches(bool $trigger_gc = TRUE, array $worker_ids = []): ResponseInterface
    {

    }


    public function set_memory_limit(int $limit_bytes, array $worker_ids = [])
    {
        $struct = [];

        //print $limit_bytes.PHP_EOL;
        //return self::get_structured_ok_response($struct);

        $struct['workers'] = $this->execute_broadcast_request(
            Method::HTTP_POST,
            '/api/admin/app-server-monitor/worker/set-memory-limit',
            Responder::class,
            'set_memory_limit',
            ['limit_bytes' => $limit_bytes]
        );

        return self::get_structured_ok_response($struct);
    }

    /**
     * Performs a graceful reload of all workers
     * @return ResponseInterface
     */
    public function reload_server(): ResponseInterface
    {

    }
}