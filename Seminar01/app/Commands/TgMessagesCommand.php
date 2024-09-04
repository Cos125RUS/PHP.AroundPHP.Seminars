<?php

namespace App\Commands;

use App\Application;
use App\Cache\Redis;
use App\EventSender\TelegramSender;
use Predis\Client;

class TgMessagesCommand extends Command
{
    protected Application $app;
    protected TelegramSender $tgApp;
    protected Redis $redis;
    private int $offset;
    private array|null $oldMessages;



    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->tgApp = new TelegramSender($this->app->env('TELEGRAM_TOKEN'));
        $this->offset = 0;
        $this->oldMessages = [];
        $this->redis=new Redis(new Client([
            'schema' => 'tcp',
            'host' => 'localhost',
            'port' => 6379
        ]));
    }

    function run(array $options = []): void
    {
        echo json_encode($this->receiveNewMessages());
    }

//    public function handle(): void
//    {
//        $messages = $this->receiveNewMessages();
//
//        foreach ($messages as $userId => $userMessages) {
//            $answerMessage = $this->handleMessagesAndReturnAnswer($userMessages);
//
//            $this->eventSender->sendMessage($userId, $answerMessage);
//        }
//    }

    private function receiveNewMessages(): array
    {
        $this->offset = $this->redis->get('tg_messages:offset', 0);

        $result = $this->tgApp->getMessages($this->offset);

        $this->redis->set('tg_messages:offset', $result['offset'] ?? 0);

        $this->oldMessages = json_decode($this->redis->get('tg_messages:old_messages'));

        $messages = [];

        foreach ($result['result'] ?? [] as $chatId => $newMessages) {
            if (isset($this->oldMessages[$chatId])) {
                $this->oldMessages[$chatId] = [...$this->oldMessages[$chatId], ...$newMessages];
            } else {
                $this->oldMessages[$chatId] = $newMessages;
            }

            $messages[$chatId] = $this->oldMessages[$chatId];
        }

        $this->redis->set('tg_messages:old_messages', json_encode($this->oldMessages));

        return $messages;
    }
}