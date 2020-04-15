<?php
declare(strict_types=1);

namespace GuzabaPlatform\AppServer\Monitor\Controllers;


use Guzaba2\Coroutine\Coroutine;
use Guzaba2\Http\Method;
use Guzaba2\Orm\Store\Memory;
use Guzaba2\Swoole\Server;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Responder extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/app-server-monitor/worker-info' => [
                Method::HTTP_GET => [self::class, 'worker_info']
            ],
            '/admin/app-server-monitor/clear-all-caches' => [ // /{percentage} - what percentage to clear
                Method::HTTP_GET => [self::class, 'clear_all_caches']
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
     * - context cache
     * - number of busy connections for each connection class
     * - number of available connections for each connection class
     * Plugins:
     * - request cache
     * @return ResponseInterface
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     */
    public function worker_info(): ResponseInterface
    {
        /** @var Server $Server */
        $Server = self::get_service('Server');

        $struct = [];

        $struct['general'] = [
            'worker_id'         => $Server->get_worker_id(),
            'pid'               => $Server->get_worker_pid(),
            'task_worker'       => $Server->is_task_worker(),
            'total_coroutines'  => Coroutine::getCid(),//get the current coroutine ID - this is the last coroutine so this is the total number,
            'active_coroutines' => count(Coroutine::listCoroutines()),//the number of active coroutines
        ];

        $struct['memory'] = [
            'usage'             => memory_get_usage(),
            'usage_real'        => memory_get_usage(TRUE),
            'peak_usage'        => memory_get_peak_usage(),
            'peak_usage_real'   => memory_get_peak_usage(TRUE),
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
}