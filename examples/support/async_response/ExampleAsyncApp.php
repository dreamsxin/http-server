<?php

use Aerys\Server,
    Amp\Async\Dispatcher,
    Amp\Async\CallResult;

class ExampleAsyncApp {
    
    private $server;
    private $dispatcher;
    private $callIdRequestMap = [];
    
    function __construct(Server $server, Dispatcher $dispatcher) {
        $this->server = $server;
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * Dispatch an asynchronous function call whose return value will be used to assign an
     * appropriate response with the server when it completes.
     */
    function __invoke(array $asgiEnv, $requestId) {
        $onResultCallback = [$this, 'onCallResult'];
        $callId = $this->dispatcher->call($onResultCallback, 'my_async_function', 'Zanzibar!');
        $this->callIdRequestMap[$callId] = $requestId;
        
        // We don't return a response now because we don't know what it is yet!
    }
    
    /**
     * Send an appropriate response back to the server when the async call returns.
     */
    function onCallResult(CallResult $result) {
        $callId = $result->getCallId();
        $requestId = $this->callIdRequestMap[$callId];
        unset($this->callIdRequestMap[$callId]);
        
        if ($result->isSuccess()) {
            $body = '<html><body><h1>Async FTW!</h1><p>' . $result->getResult() . '</p></body></html>';
            $asgiResponse = [200, 'OK', $headers = [], $body];
        } else {
            $body = '<html><body><h1>Doh!</h1><pre>'. $result->getError() .'</pre></body></html>';
            $asgiResponse = [500, 'Internal Server Error', $headers = [], $body];
        }
        
        $this->server->setResponse($requestId, $asgiResponse);
    }
    
}
