<?php
namespace AMIListener;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class AMIListener
{
    private $host = "127.0.0.1";
    private $port = 5038;
    private $username = "";
    private $secret = "";
    private $listeners = [];

    public function __construct($username,$secret,$host ="127.0.0.1",$port=5038){
        $this->username = $username;
        $this->secret = $secret;
        $this->host = $host;
        $this->port = $port;
    }

    public function addListener(callable $function,$event = ""){
        $this->listeners[] = ["function"=>$function,"event"=>$event];
    }

    private function call($parameter){
        foreach ($this->listeners as $listener){
            if ($listener["event"] === ""){
                call_user_func($listener["function"],$parameter);
            }else if (isset($parameter["Event"]) && $parameter["Event"] === $listener["event"]){
                call_user_func($listener["function"],$parameter);
            }
        }
    }

    public function start($autoReconnect = true){
        $worker = new Worker();
        $worker->onWorkerStart = function () use ($autoReconnect) {
            $ws_connection = new AsyncTcpConnection('tcp://'.$this->host.':'.$this->port);
            $ws_connection->onConnect = function (TcpConnection $connection) {
                echo "Connection Open\n";
                $connection->send( "action: login\r\n");
                $connection->send( "username: ".$this->username."\r\n");
                $connection->send( "secret: ".$this->secret."\r\n\r\n");
            };
            $ws_connection->onMessage = function (TcpConnection $connection, $data) {
                $datas = explode(PHP_EOL.PHP_EOL,$data);
                foreach ($datas as $event ){
                    if (strpos($event, "Event:") !== false){
                        $stringParams = explode(PHP_EOL,$event);
                        $parameters = [];
                        foreach ($stringParams as $parameter){
                            $parameter = explode(":",$parameter);
                            if(isset($parameter[0]) && isset($parameter[1])){
                                $parameters[trim($parameter[0])] = trim($parameter[1]);
                            }
                        }
                        $this->call($parameters);
                    }
                }
            };
            $ws_connection->onClose = function (TcpConnection $connection) use ($autoReconnect) {
                echo "Connection closed\n";
                if ($autoReconnect){
                    Worker::reloadAllWorkers();
                }
            };
            $ws_connection->connect();
        };

        Worker::runAll();
    }
	
	public static function getRecordingFile($id,$defaultPath = "/var/spool/asterisk/monitor/",$fileFormat = "wav"){
        $y = date("Y",round($id));
        $m = date("m",round($id));
        $d = date("d",round($id));
        $path = $defaultPath.$y."/".$m."/".$d."/*";
        $files = glob($path);
        if ($files !== false) {
            foreach ($files as $file) {
                if(substr($file,strlen($file)-17-strlen($fileFormat)) == $id.".".$fileFormat){
                    return file_get_contents($file);
                }
            }
        }
        return null;
    }
}