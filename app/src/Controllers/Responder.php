<?php
declare(strict_types=1);

namespace GuzabaPlatform\AppServer\Monitor\Controllers;


use Guzaba2\Base\Exceptions\RunTimeException;
use Guzaba2\Coroutine\Coroutine;
use Guzaba2\Http\Method;
use Guzaba2\Kernel\Runtime;
use Guzaba2\Orm\Store\Memory;
use Guzaba2\Swoole\Server;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;
use Guzaba2\Translator\Translator as t;

class Responder extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/app-server-monitor/worker/info' => [
                Method::HTTP_GET => [self::class, 'worker_info']
            ],
            '/admin/app-server-monitor/worker/clear-all-caches' => [ // /{percentage} - what percentage to clear
                Method::HTTP_POST => [self::class, 'clear_all_caches']
            ],
            '/admin/app-server-monitor/worker/set-memory-limit' => [ // /{percentage} - what percentage to clear
                Method::HTTP_POST => [self::class, 'set_memory_limit']
            ],
        ],
        'services'      => [
            'OrmStore',
            'QueryCache',
            'Server',
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Provides info for the worker - memory used, cached objects, cache stats...
     * Provides:
     * - used memory usage
     * - orm cache (memory store)
     * - meta store
     * - locks
     * - query cache
     * - number of busy connections for each connection class
     * - number of available connections for each connection class
     * Plugins:
     * - request cache
     * @return ResponseInterface
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \ReflectionException
     */
    public function worker_info(): ResponseInterface
    {
        /** @var Server $Server */
        $Server = self::get_service('Server');

        $struct = [];

        $struct['general'] = [
            'worker_id'                 => $Server->get_worker_id(),
            'worker_start_microtime'    => $Server->get_worker()->get_start_microtime(),
            'worker_pid'                => $Server->get_worker_pid(),
            'is_task_worker'            => $Server->is_task_worker(),
        ];
        $struct['coroutines'] = [
            'total_coroutines'          => Coroutine::getCid(),//get the current coroutine ID - this is the last coroutine so this is the total number,
            'active_coroutines'         => count(Coroutine::listCoroutines()),//the number of active coroutines
            'stats'                     => Coroutine::stats(),
        ];
        $struct['requests'] = [
            'served_requests'           => $Server->get_worker()->get_served_requests(),
            'served_pipe_requests'      => $Server->get_worker()->get_served_pipe_requests(),
            'served_console_requests'   => $Server->get_worker()->get_served_console_requests(),
            'current_requests'          => $Server->get_worker()->get_current_requests(),
        ];

        $struct['memory'] = [
            'limit'                     => Runtime::get_memory_limit(),
            'usage'                     => memory_get_usage(),
            'usage_real'                => memory_get_usage(TRUE),
            'peak_usage'                => memory_get_peak_usage(),
            'peak_usage_real'           => memory_get_peak_usage(TRUE),
        ];

        $struct['gc'] = [
            //'status'            => gc_status(),
            'enabled'           => gc_enabled(),
        ];
        $struct['gc'] += gc_status();

        //the Memory store should be the first store (if it is used).
        /** @var Memory $OrmStore */
        $OrmStore = self::get_service('OrmStore');
        if ($OrmStore instanceof Memory) {
            $struct['memory_store'] = $OrmStore->get_stats();
        } else {
            //skip this section
        }

        $QueryCache = self::get_service('QueryCache');
        $struct['query_cache'] = $QueryCache->get_stats();

        return self::get_structured_ok_response($struct);
    }

    /**
     * @param int $limit_bytes
     * @return ResponseInterface
     * @throws RunTimeException
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     */
    public function set_memory_limit(int $limit_bytes): ResponseInterface
    {
        /** @var Server $Server */
        $Server = self::get_service('Server');

        $struct = [];

        $old_limit = Runtime::get_memory_limit();
        Runtime::set_memory_limit($limit_bytes);
        $struct['message'] = sprintf(t::_('The memory limit for worker ID %1s was changed from %2s to %3s bytes.'), $Server->get_worker_id(), $old_limit, $limit_bytes);
        return self::get_structured_ok_response($struct);
    }

    public function clear_all_caches(bool $trigger_gc): ResponseInterface
    {

    }




}