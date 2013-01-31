<?php

namespace ShonM\ResqueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $resque = $this->get('resque');

        return $this->render('ShonMResqueBundle:Resque:index.html.twig', array(
            'resque' => $resque,
            'queues' => count($resque->queues()),
            'workers' => count($resque->workers()),
        ));
    }

    public function queueAction($queue = null)
    {
        $resque = $this->get('resque');

        return $this->render('ShonMResqueBundle:Resque:queues.html.twig', array(
            'queues' => $resque->queues(),
            'queue' => ($queue) ? $resque->queues($queue) : false,
            'workers' => $resque->workers($queue),
        ));
    }

    public function workersAction($worker = null)
    {
        $resque = $this->get('resque');

        return $this->render('ShonMResqueBundle:Resque:workers.html.twig', array(
            'workers' => $resque->workers(),
            'worker' => ($worker) ? $resque->worker($worker) : false,
        ));
    }

    // TODO: SM: Move the innards of this method into a service
    public function failedAction($method, $id)
    {
        $redis = $this->get('resque')->redis();

        if ($method) {
            switch ($method) {
                case 'retry':
                    $job = json_decode($redis->get('failed:' . $id));
                    $this->get('resque')->add($job->payload->class, $job->queue, (array) reset($job->payload->args));
                    $redis->del('failed:' . $id);
                    break;
                case 'again':
                    foreach ($redis->keys('failed:*') as $id) {
                        list($prefix, $queue, $id) = explode(':', $id);
                        $job = json_decode($redis->get('failed:' . $id));
                        $this->get('resque')->add($job->payload->class, $job->queue, (array) reset($job->payload->args));
                        $redis->del('failed:' . $id);
                    }
                    break;
                case 'clear':
                    $redis->del('failed:' . $id);
                    break;
                case 'flush':
                    foreach ($redis->keys('failed:*') as $id) {
                        list($prefix, $queue, $id) = explode(':', $id);
                        $redis->del('failed:' . $id);
                    }
                    break;
                default:
                    break;
            }

            return $this->redirect($this->generateUrl('resque_failed'));
        }

        $failedIds = $redis->keys('failed:*');

        $failed = array();
        foreach ($failedIds as $id) {
            list($prefix, $queue, $id) = explode(':', $id);

            $failed[$id] = json_decode($redis->get($queue . ':' . $id));
        }

        return $this->render('ShonMResqueBundle:Resque:failed.html.twig', array(
            'failed' => $failed,
        ));
    }
}
