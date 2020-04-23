<?php
declare(strict_types=1);

namespace GuzabaPlatform\AppServer\Monitor\Controllers;

use Guzaba2\Authorization\CurrentUser;
use Guzaba2\Coroutine\Coroutine;
use Guzaba2\Http\Method;
use Guzaba2\Http\StatusCode;
use Guzaba2\Orm\Store\Memory;
use Guzaba2\Swoole\IpcRequest;
use Guzaba2\Swoole\Server;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class Monitor extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/app-server-monitor'                             => [
                Method::HTTP_GET                                        => [self::class, 'main'],
            ],
            '/admin/app-server-monitor/clear-orm-cache'             => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                                       => [self::class, 'clear_orm_cache'],
            ],
            '/admin/app-server-monitor/clear-query-cache'           => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                                       => [self::class, 'clear_query_cache'],
            ],
            '/admin/app-server-monitor/clear-all-caches'            => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                                       => [self::class, 'clear_all_caches'],
            ],
            '/admin/app-server-monitor/trigger-gc'                  => [ //the percentage and workers_ids should be provided in the POST
                Method::HTTP_POST                                       => [self::class, 'trigger_gc'],
            ],
            '/admin/app-server-monitor/set-memory-limit'            => [
                Method::HTTP_POST                                       => [self::class, 'set_memory_limit']
            ],
            '/admin/app-server-monitor/worker-supported-actions'    => [
                Method::HTTP_GET                                        => [self::class, 'worker_supported_actions'],
            ],
            '/admin/app-server-monitor/server-supported-actions'    => [
                Method::HTTP_GET                                        => [self::class, 'server_supported_actions'],
            ],
//            '/admin/app-server-monitor/stop'                        => [
//                Method::HTTP_POST                                       => [self::class, 'stop_worker'],
//            ]
        ],
        'services'      => [
            'Server',
            'CurrentUser',//needed for localization
            'OrmStore',
            'QueryCache',
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

        $response_microtime = microtime(TRUE);

        $struct += $this->execute_structured_action('worker_supported_actions');
        $struct += $this->execute_structured_action('server_supported_actions');//must be before the sections below as then more elements are added to the 'server' section

        $struct['server']['all_workers_ids'] = $Server->get_all_workers_ids();

        $struct['server']['data']['general'] = [
            'responding_worker_id'              => $Server->get_worker_id(),//the worker ID that sent this response
            'responding_cid'                    => Coroutine::getCid(),
            'master_pid'                        => $Server->get_master_pid(),
            'manager_pid'                       => $Server->get_manager_pid(),
            'server_start_microtime'            => $Server->get_start_microtime(),
            'server_start_formatted_time'       => date( $CurrentUser->get()->get_date_time_format(), (int) $Server->get_start_microtime() ),
            'response_microtime'                => $response_microtime,
            'response_formatted_time'           => date( $CurrentUser->get()->get_date_time_format(), (int) $response_microtime),
        ];
        $struct['server']['data']['stats'] = $Server->stats();
        $struct['server']['data']['options'] = $Server->get_all_options();


        return self::get_structured_ok_response($struct);
    }

    public function server_supported_actions(): ResponseInterface
    {
        $struct = [];
        $struct['server']['supported_actions'] = [
            [
                'name'      => sprintf(t::_('Reload Workers')),
                'method'    => Method::METHODS_MAP[Method::HTTP_POST],
                'route'     => '/admin/app-server-monitor/reload',
                'arguments' => [
                    [
                        'text'  => sprintf(t::_('Only Task Workers')),
                        'name'  => 'only_task_workers',
                        'value' => FALSE,
                    ]
                ],
            ],
            //the $Server->shutdown() is not supported... not much point...
            //TODO there should be are restart method, but this can not use shutdown() & start() as the parsed files will be the same
            //instead the process should be stopped and started again on clean (reread the registry and generated files)
            //if the server it stopped there should be an external service that restarts it (for example the docker service)
        ];
        return self::get_structured_ok_response($struct);
    }

    public function worker_supported_actions(): ResponseInterface
    {
        $struct = [];
        $struct['worker']['supported_actions'] = [];

        $struct['worker']['supported_actions'][] = [
            'name'      => sprintf(t::_('Trigger GC')),
            'method'    => Method::METHODS_MAP[Method::HTTP_POST],
            'route'     => '/admin/app-server-monitor/trigger-gc',
            'arguments' => [],
        ];

        /** @var Memory $OrmStore */
        $OrmStore = self::get_service('OrmStore');
        if ($OrmStore instanceof Memory) {
            $struct['worker']['supported_actions'][] = [
                'name'      => sprintf(t::_('Clear ORM cache')),
                'method'    => Method::METHODS_MAP[Method::HTTP_POST],
                'route'     => '/admin/app-server-monitor/clear-orm-cache',
                'arguments' => [
                    [
                        'text'  => sprintf(t::_('Percentage')),
                        'name'  => 'percentage',
                        'value' => 100,
                    ],
                    [
                        'text'  => sprintf(t::_('Trigger GC')),
                        'name'  => 'trigger_gc',
                        'value' => TRUE,
                    ]
                    //and of course there will be always provided the $worker_ids array
                ],
            ];
        }

        if (self::has_service('QueryCache')) {
            $struct['worker']['supported_actions'][] = [
                'name'      => sprintf(t::_('Clear Query cache')),
                'method'    => Method::METHODS_MAP[Method::HTTP_POST],
                'route'     => '/admin/app-server-monitor/clear-query-cache',
                'arguments' => [
                    [
                        'text'  => sprintf(t::_('Percentage')),
                        'name'  => 'percentage',
                        'value' => 100,
                    ],
                    [
                        'text'  => sprintf(t::_('Trigger GC')),
                        'name'  => 'trigger_gc',
                        'value' => TRUE,
                    ]
                    //and of course there will be always provided the $worker_ids array
                ],
            ];
        }

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
     * @param array $workers_ids
     * @return ResponseInterface
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function clear_all_caches(bool $trigger_gc = TRUE, array $workers_ids = []): ResponseInterface
    {
        $struct = [];

        //print $limit_bytes.PHP_EOL;
        //return self::get_structured_ok_response($struct);
        if (!count($workers_ids)) {
            /** @var Server $Server */
            $Server = self::get_service('Server');
            $workers_ids = $Server->get_all_workers_ids();
        }

        $struct['workers'] = $this->execute_multicast_request(
            $workers_ids,
            Method::HTTP_POST,
            '/api/admin/app-server-monitor/worker/clear-all-caches',
            Responder::class,
            'clear_all_caches',
            ['trigger_gc' => $trigger_gc]
        );

        return self::get_structured_ok_response($struct);
    }

    /**
     * @param int $limit_bytes
     * @param int[] $workers_ids
     * @return ResponseInterface
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    public function set_memory_limit(int $limit_bytes, array $workers_ids = [])
    {
        $struct = [];

        //print $limit_bytes.PHP_EOL;
        //return self::get_structured_ok_response($struct);
        if (!count($workers_ids)) {
            /** @var Server $Server */
            $Server = self::get_service('Server');
            $workers_ids = $Server->get_all_workers_ids();
        }

        $struct['workers'] = $this->execute_multicast_request(
            $workers_ids,
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