<?php
namespace App\EventSender;

use App\Queue\Queue;

class EventSender
{
    private TelegramApi $telegram;
    private Queue $queue;
    private string $receiver;
    private string $message;

    public function __construct(TelegramApi $telegram, Queue $queue)
    {
        $this->telegram = $telegram;
        $this->queue = $queue;
    }

    public function sendMessage(string $receiver, string $message): void
    {
        $this->toQueue($receiver, $message);
//        $this->telegram->sendMessage($receiver, $message);
//        echo date('d.m.y H:i') . " Я отправил сообщение $message получателю с id $receiver\n";
    }

    public function handle(): void
    {
        $this->telegram->sendMessage($this->receiver, $this->message);
    }

    public function toQueue (... $args): void
    {
        $this->receiver = $args[0];
        $this->message = $args[1];
        $this->queue->sendMessage(serialize($this));
    }
}